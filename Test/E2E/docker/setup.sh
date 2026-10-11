#!/usr/bin/env bash
# Installs Magento on the first start (kept in the "magento" volume), upgrades it with the
# mounted module on every start, then serves it with the PHP built-in server.
set -euo pipefail

ROOT=/var/www/magento
VERSION="${MAGENTO_VERSION:-2.4.7-p10}"
BASE_URL="${MAGENTO_BASE_URL:-http://localhost:8080/}"
export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1
cd "$ROOT"

if [ ! -f bin/magento ]; then
    echo "[esr] Downloading Magento $VERSION from the Mage-OS mirror"
    rm -rf /tmp/project
    composer create-project --no-install --no-interaction \
        --repository-url=https://mirror.mage-os.org/ "magento/project-community-edition:$VERSION" /tmp/project
    cd /tmp/project
    composer config --unset repositories.0 || true
    composer config repositories.mageos composer https://mirror.mage-os.org/
    composer config --no-plugins allow-plugins true
    # Test environment only: older patch releases have published advisories.
    php -r '$f = "composer.json"; $j = json_decode(file_get_contents($f), true);
        $j["config"]["policy"]["advisories"]["block"] = false; $j["config"]["audit"]["block-insecure"] = false;
        file_put_contents($f, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));'
    composer install --no-interaction --no-progress
    cp -a /tmp/project/. "$ROOT/"
    rm -rf /tmp/project
    cd "$ROOT"
fi

# Every start: db and search start together with this container.
echo "[esr] Waiting for the database and OpenSearch"
until php -r 'new PDO("mysql:host=db;dbname=magento", "magento", "magento");' 2>/dev/null; do sleep 2; done
until php -r 'exit(@file_get_contents("http://search:9200/_cluster/health") === false ? 1 : 0);'; do sleep 2; done

if [ ! -f app/etc/env.php ]; then
    echo "[esr] Installing Magento"
    php bin/magento setup:install --base-url="$BASE_URL" \
        --db-host=db --db-name=magento --db-user=magento --db-password=magento \
        --admin-firstname=Admin --admin-lastname=E2E --admin-email=admin@example.com \
        --admin-user=admin --admin-password=Admin123456 --backend-frontname=admin \
        --language=en_US --currency=BRL --timezone=America/Sao_Paulo --use-rewrites=0 \
        --search-engine=opensearch --opensearch-host=search --opensearch-port=9200 \
        --cleanup-database
    php bin/magento module:disable -q Magento_TwoFactorAuth Magento_AdminAdobeImsTwoFactorAuth
    php bin/magento deploy:mode:set developer -q
    php bin/magento indexer:set-mode realtime
    # Same admin settings the Magento Functional Testing Framework requires.
    php bin/magento config:set admin/security/use_form_key 0
    php bin/magento config:set admin/security/session_lifetime 86400
    php bin/magento config:set admin/captcha/enable 0
    php bin/magento config:set customer/captcha/enable 0
    php bin/magento config:set general/country/default BR
fi

echo "[esr] Upgrading with the mounted module"
# Tests log in as "e2e" (English interface); "admin" is left for people, in any language.
php bin/magento admin:user:create --admin-user=e2e --admin-password=E2eTests123 \
    --admin-email=e2e@example.com --admin-firstname=E2E --admin-lastname=Tests >/dev/null 2>&1 || true
# Admin pop-ups (usage data, release notes) would cover the pages under test.
php bin/magento module:disable -q Magento_AdminAnalytics Magento_ReleaseNotification || true
php bin/magento setup:upgrade --no-interaction
# "e2e-tester" may only open the Rule Tester: the permission tests log in with it.
php <<'PHP'
<?php
require 'app/bootstrap.php';
$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$role = $om->create(\Magento\Authorization\Model\Role::class)->load('ESR Rule Tester Only', 'role_name');
if (!$role->getId()) {
    $role->setRoleName('ESR Rule Tester Only')
        ->setRoleType(\Magento\Authorization\Model\Acl\Role\Group::ROLE_TYPE)
        ->setUserType(\Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN)
        ->setParentId(0)
        ->setTreeLevel(1)
        ->save();
    $om->create(\Magento\Authorization\Model\Rules::class)
        ->setRoleId($role->getId())
        ->setResources([
            'Magento_Backend::stores',
            'WillSco_EasyShippingRulesM2::easyshippingrules',
            'WillSco_EasyShippingRulesM2::tester',
        ])
        ->saveRel();
}

$user = $om->create(\Magento\User\Model\User::class)->loadByUsername('e2e-tester');
if (!$user->getId()) {
    $user->setData([
        'username' => 'e2e-tester',
        'firstname' => 'E2E',
        'lastname' => 'Tester',
        'email' => 'e2e-tester@example.com',
        'password' => 'E2eTests123',
        'interface_locale' => 'en_US',
        'is_active' => 1,
    ])->setRoleId($role->getId())->save();
}
PHP
php bin/magento cache:flush

echo "[esr] Serving $BASE_URL"
exec env PHP_CLI_SERVER_WORKERS=8 php -S 0.0.0.0:8080 -t pub/ phpserver/router.php
