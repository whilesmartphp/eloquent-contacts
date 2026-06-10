## [1.0.0] - 2026-06-10
- Polymorphic contacts attachable to any model (customers, vendors, anything) via a contactable morph
- Owner-scoped through owner-access, with a HasContacts trait exposing contacts() and primaryContact()
- One primary contact per parent, enforced automatically on save
