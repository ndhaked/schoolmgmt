# School Management System

A role-based School Management & Online Examination system built with **Laravel 11** and **Livewire 3** (real-time UI — no full page reloads anywhere in the app). Four separate panels — Admin, Teacher, Student, Parent — each showing only what that role is allowed to see and do.

**Tech stack:** Laravel 11 · Livewire 3 (Volt) · Alpine.js · Tailwind CSS · MySQL · Spatie Laravel-Permission (roles)

---

## Panels & Roles

The system has four logins, each landing on its own dashboard and sidebar:

| Role | Can do |
|---|---|
| **Admin** | Full control of the school: academic structure, people, question bank, exams, results |
| **Teacher** | Manage only their own assigned classes/subjects: question bank, exams, results |
| **Student** | View their exams, take exams online, view results and marksheet |
| **Parent** | View their linked child's/children's declared results and marksheet |

Access is enforced on the server for every page and every action — not just hidden buttons. For example, a teacher can never create an exam or question for a class/subject they aren't assigned to, even by tampering with the request.

---

## 1. Admin Panel

### Academics
- **Academic Years** — create/edit/delete academic years; mark one as "current" (automatically unmarks the previous one)
- **Classes & Sections** — create classes tied to an academic year; add/edit/delete sections within each class (e.g. Class 10 → A, B, C)
- **Subjects** — create subjects with a unique code; assign each subject to one or more classes

### People
- **Students** — add a student (auto-creates their login account with a one-time temporary password shown on screen); assign class, section, roll number, admission number, guardian details; edit/delete
- **Teachers** — add a teacher (auto-creates login); assign them to specific **class + subject** combinations — this controls exactly what they can manage elsewhere in the app
- **Parents** — add a parent (auto-creates login); link them to one or more student "children" via a searchable picker

### Examination
- **Question Bank** — add multiple-choice questions (2–6 options, exactly one correct answer, configurable marks and negative marking) tagged by class + subject
- **Exams** — create exams (title, class, subject, schedule, duration, pass %); pull questions from the bank into the exam; **Publish/Unpublish** (can't publish with zero questions)
- **Results** — view submitted attempts per exam with a live class-performance summary (average / highest / lowest / pass rate); **Declare Results** once the exam window has closed (locks in visibility for students/parents); view any student's full marksheet

---

## 2. Teacher Panel

Everything below is automatically scoped to only the classes/subjects an admin has assigned to that teacher.

- **Dashboard** — quick overview
- **My Classes** — list of assigned class + subject pairs, each with a live student-count and an expandable roster (name, roll number, section)
- **Question Bank** — add/edit/delete their own questions for their assigned classes/subjects; can see (read-only) questions added by others for the same class/subject
- **Exams** — create/manage exams for their assigned classes/subjects only; attach questions; publish/declare
- **Results** — same as admin's Results view, scoped to their own exams; view any of their students' marksheets

---

## 3. Student Panel

- **Dashboard**
- **My Exams** — see upcoming / live / completed exams for their class, with live status badges
- **Online Exam Taking** — the core real-time feature:
  - Live countdown timer synced to the exam's actual deadline (survives page refresh)
  - Question navigator (jump to any question, see which are answered at a glance)
  - Every answer **autosaves instantly** on click — no "Save" button, no risk of losing progress
  - **Auto-submit** when time runs out, even if the student does nothing
  - Once submitted, the attempt is locked — no going back to change answers
- **My Results** — declared results only (undeclared exams don't leak scores early), with Pass/Fail per subject
- **My Marksheet** — printable report card aggregating every declared exam across all subjects, with per-subject grade (A+/A/B/C/F) and an overall Pass/Fail (fails if any single subject fails)

---

## 4. Parent Panel

- **Dashboard**
- **Child's Results** — one section per linked child, showing only declared results for that child (never another student's data)
- **Marksheet** — same printable report card as the student view, for each linked child

---

## Key Design Points

- **No page reloads anywhere** — every list, form, modal, and the exam timer itself update live via Livewire; navigation between pages uses `wire:navigate` for an SPA-like feel without building a separate frontend app.
- **Automatic evaluation** — MCQ answers are scored the instant a student selects them (including negative marking); nothing needs a manual grading step.
- **Result declaration gate** — scores are computed immediately on submission but stay hidden from students/parents until a teacher/admin explicitly "Declares" the exam. This prevents scores leaking before a teacher has reviewed the class.
- **Ownership & scoping enforced server-side** — every restriction (teacher's assigned classes, parent's linked children, student's own attempts) is checked in the backend, not just hidden in the UI.
- **Automated test coverage** — 86 passing feature tests covering every module's create/edit/delete flows, access control, and scoring logic, acting as a regression safety net for future changes.

---

## Demo / Test Accounts

Pre-seeded so the full flow can be demoed end-to-end without manual setup. Password for all: **`password`**

| Role | Email | Notes |
|---|---|---|
| Admin | `admin@school.test` | Full access |
| Teacher | `teacher@school.test` | Priya Verma — Class 10, Mathematics & Science |
| Student | `student1@school.test` | Aarav Sharma — roll 1 |
| Student | `student2@school.test` | Diya Patel — roll 2 |
| Student | `student3@school.test` | Kabir Singh — roll 3 |
| Parent | `parent@school.test` | Rohit Sharma — linked to student1 |

Seeded demo data includes:
- **"Live Demo Quiz — Mathematics"** — published and currently open, ready to be taken by any Class 10 student
- **"Past Demo Quiz — Science"** — already finished, submitted, and **results declared** (student1 passed, student2 failed) — ready to view in Results/Marksheet immediately

To re-seed this data at any time: `php artisan db:seed`

---

## Local Setup

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# configure DB_* in .env for your MySQL instance, then:
php artisan migrate
php artisan db:seed
php artisan serve
```
