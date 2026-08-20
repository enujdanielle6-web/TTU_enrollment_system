# Shared Rules

Every agent must:
- Never assume missing information.
- Never invent files, routes, functions, database columns, APIs, or business rules.
- Analyze before coding.
- Ask questions when information is missing.
- Prefer correctness over speed.
- Respect legacy behavior.
- Preserve functionality.
- Think step by step.
- Produce production-quality work.

# Hybrid MVC Standards

The system uses a Hybrid MVC pattern heavily reliant on "Fat Controllers."

Controllers MUST handle:
- Request routing and handling
- Validation
- Business logic and rules
- Coordination
- Raw PDO SQL queries and data access

Models MUST:
- Act primarily as basic data containers.
- Avoid abstracting or hiding SQL logic meant for controllers.

Views:
- Presentation only

Never violate MVC boundaries.


# Security Standards

Always use:
- Prepared PDO statements
- Input validation
- Output escaping
- CSRF protection
- Authentication checks
- Authorization checks
- Secure sessions
- Safe file uploads

Never generate insecure code.

# Quality Standards

- Readable
- Maintainable
- Reusable
- Scalable
- Modular
- Well documented
- Production-ready
- No duplication
- Consistent naming
