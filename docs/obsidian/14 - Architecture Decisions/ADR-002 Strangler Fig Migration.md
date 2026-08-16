# ADR-002: Strangler Fig Migration

## Context
The system was originally built as procedural PHP files scattered across directories.

## Problem
A complete rewrite was unfeasible due to time constraints, but the procedural code was unmaintainable and insecure. Links to old `.php` files were already bookmarked by users and hardcoded in various places.

## Decision
Implement a custom MVC Framework, but map the old procedural URLs directly to the new Controllers.
Use Apache `.htaccess` to block direct execution of `.php` scripts and funnel everything to `public/index.php`.

## Reasoning
The Strangler Fig pattern allows the UI and URLs to remain completely unchanged while the backend is entirely replaced.

## Consequences
- **Positive:** Zero downtime migration. Links aren't broken.
- **Positive:** Centralized security via Middleware immediately protects all legacy routes.
- **Negative:** URLs still visually expose `.php` extensions, which is non-standard for modern MVC.
- **Negative:** To move quickly, developers copy-pasted procedural logic straight into the new controllers, resulting in the "Fat Controller" problem (see [[Known Issues]]).

## Status
`Current / Active`
