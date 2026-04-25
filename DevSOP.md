**Team SOP: Laravel Git + DB Workflow (Main + Frontend + Backend branches)**

### 1. Branch Strategy (Never touch main until release)
- Main = stable production-ready code only.
- Frontend & Backend = daily work branches.
- All devs work ONLY on their assigned branch locally.

### 2. Daily Developer Workflow (EOD & Start of Day)
**End of Day (EOD):**
1. `git add .`
2. `git commit -m "EOD: [short description]"`
3. `git push origin <your-branch>` (frontend or backend)

**Start of Day (every morning):**
1. `git checkout <your-branch>`
2. `git fetch origin`
3. `git merge origin/frontend` (if you are on backend)  
   OR  
   `git merge origin/backend` (if you are on frontend)
4. If conflicts → fix them, commit, push.
5. Run: `php artisan migrate` (for any new DB changes)
6. Update `.env` manually from `.env.example` if changed.

### 3. Sync Changes Between Branches (without main)
- To bring frontend changes into backend:  
  `git checkout backend` → `git fetch` → `git merge origin/frontend`
- To bring backend into frontend: reverse above.
- Always do this daily after morning pull.

### 4. DB, .env & Data Handling
- **Schema changes:** Always create migration in your branch. Commit it.
- After any merge: every dev runs `php artisan migrate`
- **.env / config:** Update `.env.example` in Git. Each dev copies changes to local `.env` manually.
- **Table data / seeders:** Do NOT commit real data. Use seeders or share SQL dump manually via Slack/Drive.

### 5. Changelog (Mandatory)
- Keep `CHANGELOG.md` in repo root.
- Every commit/merge: append at top:
  ```
  ## YYYY-MM-DD
  - Added: ...
  - Changed: ...
  - Fixed: ...
  ```
- Commit it with your code changes.

### 6. Rollback if Something Breaks
**Code rollback:**
- Safe: `git revert <bad-commit-hash>`
- Hard: `git reset --hard <good-commit-hash>` then `git push --force-with-lease`

**DB rollback:**
- `php artisan migrate:rollback`
- Data: restore from your manual backup (taken before risky changes).

Follow this SOP strictly. One daily merge + migrate keeps everyone in sync and main clean.