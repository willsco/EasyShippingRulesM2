# Security Policy

## Supported versions

Security fixes are released for the latest minor version only.

| Version | Supported |
| ------- | --------- |
| 1.0.x   | Yes       |
| < 1.0   | No        |

The module supports the Magento and PHP versions listed in the [README](README.md#requirements).
Vulnerabilities in Magento itself should be reported to Adobe, not here.

## Reporting a vulnerability

**Please do not open a public issue, pull request or discussion for security problems.**

Report it privately through GitHub:

1. Go to the [Security tab](https://github.com/willsco/EasyShippingRulesM2/security) of this repository.
2. Click **Report a vulnerability**.
3. Fill in the advisory form.

Please include:

- the module version, Magento version and PHP version;
- the affected area (admin grid, controller, rule conditions, rate table import, REST/GraphQL, etc.);
- steps to reproduce, or a proof of concept;
- the impact you expect (for example: XSS in the admin, privilege escalation, data exposure,
  manipulation of shipping prices at checkout);
- any suggested fix, if you have one.

## What to expect

- **Acknowledgement** within 5 business days.
- **Initial assessment** (confirmed, needs more information, or not a vulnerability) within 10
  business days.
- For confirmed issues, a fix is prepared in a private fork and released as a patch version. The
  advisory is published once the fix is available, with credit to the reporter unless you prefer to
  remain anonymous.

Please give us a reasonable amount of time to release a fix before disclosing the issue publicly.

## Scope

In scope:

- code in this repository (`WillSco_EasyShippingRulesM2`);
- admin ACL bypasses, CSRF, XSS or injection in the module's admin pages and controllers;
- issues in the rate table CSV import/export;
- ways for a storefront customer or API client to obtain shipping rates, prices or hidden methods
  they should not get.

Out of scope:

- vulnerabilities in Magento / Adobe Commerce core or third-party modules;
- issues that require an attacker who already has full admin access or server access;
- findings from automated scanners without a demonstrated impact.
