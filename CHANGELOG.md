## [1.1.0] - 2026-08-01
- Allow applications to configure contact routes, resources, response formatting, and controllers

## [1.0.1] - 2026-06-10
- Require the released owner-access ^1.0 (was a dev branch)

## [1.0.0] - 2026-06-10
- Polymorphic contacts attachable to any model (customers, vendors, anything) via a contactable morph
- Owner-scoped through owner-access, with a HasContacts trait exposing contacts() and primaryContact()
- Structured names (first_name, last_name, and a full_name accessor) plus email, phone, title, and address
- One primary contact per parent, enforced automatically on save
- Swappable Contact model via the contacts.model config
- Optional UUID primary keys via the contacts.uuids config
