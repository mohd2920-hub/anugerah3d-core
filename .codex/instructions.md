# Codex Instructions

This is an AI-first Laravel project.

Before doing any coding, Codex must read:
- README.md
- .ai/project_rules.md
- .ai/architecture.md
- .ai/database.md
- .codex/instructions.md
- TASK.md

Rules:
- Keep Laravel source inside /src.
- Use MariaDB.
- Use S3 bucket anugerah3d-content.
- Do not commit .env.
- Store picture fields as S3 object keys.
- Log important actions in activities table.
- Use adm_name, agt_name, cust_name, prd_name.
- Update documentation when changing database or architecture.
- Keep MVP simple.
