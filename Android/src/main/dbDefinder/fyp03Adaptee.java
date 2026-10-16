package com.example.teambox_sk2.dbDefinder;

import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;
import android.util.Log;

public class fyp03Adaptee extends SQLiteOpenHelper {
    private static final String DATABASE_NAME = "teambox_local.db";
    private static final int DATABASE_VERSION = 7; // Bumped version for teams support
    private static final String TAG = "fyp03Adaptee";

    private static Context appContext;

    public static void initializeContext(Context context) {
        appContext = context.getApplicationContext();
    }

    public fyp03Adaptee() {
        super(appContext, DATABASE_NAME, null, DATABASE_VERSION);
    }

    @Override
    public void onCreate(SQLiteDatabase db) {
        // Core tables matching the SQL dump
        db.execSQL("CREATE TABLE users (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "username TEXT NOT NULL, " +
                "email TEXT UNIQUE NOT NULL, " +
                "password_hash TEXT NOT NULL, " +
                "role TEXT NOT NULL, " +
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");

        db.execSQL("CREATE TABLE teachers (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "user_id INTEGER NOT NULL, " +
                "teacher_code TEXT NOT NULL UNIQUE, " +
                "hire_date TEXT, " +
                "department TEXT, " +
                "title TEXT, " +
                "FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE students (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "user_id INTEGER NOT NULL UNIQUE, " +
                "student_number TEXT NOT NULL UNIQUE, " +
                "major TEXT, " +
                "grade_level INTEGER, " +
                "FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE courses (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "code TEXT NOT NULL UNIQUE, " +
                "name TEXT NOT NULL, " +
                "teacher_id INTEGER NOT NULL, " +
                "semester TEXT, " +
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE course_enrollments (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "course_id INTEGER NOT NULL, " +
                "student_id INTEGER NOT NULL, " +
                "enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE projects (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "name TEXT NOT NULL, " +
                "category TEXT, " +
                "course_id INTEGER, " +
                "supervisor_id INTEGER NOT NULL, " +
                "period_start TEXT, " +
                "period_end TEXT, " +
                "status TEXT DEFAULT 'Recruiting', " +
                "progress INTEGER DEFAULT 0, " +
                "description TEXT, " +
                "FOREIGN KEY (supervisor_id) REFERENCES teachers(id), " +
                "FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL);");

        db.execSQL("CREATE TABLE groups (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "project_id INTEGER NOT NULL, " +
                "course_id INTEGER, " +
                "name TEXT NOT NULL, " +
                "leader_id INTEGER, " +
                "creator_id INTEGER, " +
                "max_members INTEGER DEFAULT 4, " +
                "status TEXT DEFAULT 'Recruiting', " +
                "formed_at TEXT, " +
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL, " +
                "FOREIGN KEY (leader_id) REFERENCES students(id) ON DELETE SET NULL, " +
                "FOREIGN KEY (creator_id) REFERENCES students(id) ON DELETE SET NULL);");

        db.execSQL("CREATE TABLE group_members (" +
                "group_id INTEGER NOT NULL, " +
                "student_id INTEGER NOT NULL, " +
                "role TEXT DEFAULT 'member', " +
                "joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "PRIMARY KEY (group_id, student_id), " +
                "FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE tasks (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "project_id INTEGER NOT NULL, " +
                "group_id INTEGER NOT NULL, " +
                "title TEXT NOT NULL, " +
                "description TEXT, " +
                "assigned_to INTEGER, " +
                "created_by INTEGER NOT NULL, " +
                "status TEXT DEFAULT 'Pending', " +
                "priority TEXT DEFAULT 'Medium', " +
                "due_date TEXT, " +
                "completed_at TEXT, " +
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (assigned_to) REFERENCES students(id) ON DELETE SET NULL);");

        db.execSQL("CREATE TABLE files (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "project_id INTEGER NOT NULL, " +
                "group_id INTEGER NOT NULL, " +
                "uploader_id INTEGER NOT NULL, " +
                "file_name TEXT NOT NULL, " +
                "file_size TEXT, " +
                "file_type TEXT, " +
                "status TEXT DEFAULT 'Unviewed', " +
                "uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "reviewed_by INTEGER, " +
                "reviewed_at TEXT, " +
                "review_note TEXT, " +
                "FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (uploader_id) REFERENCES students(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (reviewed_by) REFERENCES teachers(id) ON DELETE SET NULL);");

        db.execSQL("CREATE TABLE messages (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "group_id INTEGER NOT NULL, " +
                "sender_id INTEGER NOT NULL, " +
                "content TEXT, " +
                "attachment_id INTEGER, " +
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (sender_id) REFERENCES students(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (attachment_id) REFERENCES files(id) ON DELETE SET NULL);");

        db.execSQL("CREATE TABLE announcements (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "teacher_id INTEGER NOT NULL, " +
                "title TEXT NOT NULL, " +
                "message TEXT NOT NULL, " +
                "target_group_id INTEGER, " +
                "target_project_id INTEGER, " +
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE notifications (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "user_id INTEGER NOT NULL, " +
                "type TEXT NOT NULL, " +
                "title TEXT NOT NULL, " +
                "message TEXT, " +
                "link TEXT, " +
                "is_read INTEGER DEFAULT 0, " +
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE milestones (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "project_id INTEGER NOT NULL, " +
                "name TEXT NOT NULL, " +
                "start_date TEXT NOT NULL, " +
                "end_date TEXT NOT NULL, " +
                "status TEXT DEFAULT 'Pending', " +
                "progress INTEGER DEFAULT 0, " +
                "FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE invitations (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "group_id INTEGER NOT NULL, " +
                "inviter_id INTEGER NOT NULL, " +
                "invitee_id INTEGER NOT NULL, " +
                "status TEXT DEFAULT 'Pending', " +
                "sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "responded_at TEXT, " +
                "FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (inviter_id) REFERENCES students(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (invitee_id) REFERENCES students(id) ON DELETE CASCADE);");

        db.execSQL("CREATE TABLE leader_votes (" +
                "id INTEGER PRIMARY KEY AUTOINCREMENT, " +
                "group_id INTEGER NOT NULL, " +
                "voter_id INTEGER NOT NULL, " +
                "candidate_id INTEGER NOT NULL, " +
                "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, " +
                "FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (voter_id) REFERENCES students(id) ON DELETE CASCADE, " +
                "FOREIGN KEY (candidate_id) REFERENCES students(id) ON DELETE CASCADE);");

        // --- Seed Initial Data ---
        db.execSQL("INSERT INTO users (id, username, email, password_hash, role) VALUES (1, 'Esther Yuen', 'wyyuen@vtc.edu.hk', '12345', 'teacher');");
        db.execSQL("INSERT INTO users (id, username, email, password_hash, role) VALUES (2, 'Dr. Alan Ho', 'alanho@vtc.edu.hk', '12345', 'teacher');");
        db.execSQL("INSERT INTO users (id, username, email, password_hash, role) VALUES (6, 'CHAN Ho Yin', '260222111@stu.vtc.edu.hk', '12345', 'student');");
        db.execSQL("INSERT INTO users (id, username, email, password_hash, role) VALUES (7, 'CHUI Ka Chun', '260222112@stu.vtc.edu.hk', '12345', 'student');");

        db.execSQL("INSERT INTO teachers (id, user_id, teacher_code, hire_date, department, title) VALUES (1, 1, 'T48291', '2022-09-01', 'Information Technology', 'Senior Lecturer');");
        db.execSQL("INSERT INTO students (id, user_id, student_number, major, grade_level) VALUES (1, 6, 'S48291537', 'Software Engineering', 14);");
        db.execSQL("INSERT INTO students (id, user_id, student_number, major, grade_level) VALUES (2, 7, 'S15942736', 'Software Engineering', 14);");

        db.execSQL("INSERT INTO projects (id, name, category, supervisor_id, period_start, period_end, status, progress, description) VALUES (1, 'TeamBox', 'Software Engineering', 1, '2026-09-01', '2027-04-30', 'Executing', 60, 'Cross-platform collaboration and management system for student teams.');");
        db.execSQL("INSERT INTO groups (id, project_id, name, max_members, status) VALUES (1, 1, 'Team 01', 4, 'Executing');");
        db.execSQL("INSERT INTO group_members (group_id, student_id, role) VALUES (1, 1, 'leader');");
        db.execSQL("INSERT INTO group_members (group_id, student_id, role) VALUES (1, 2, 'member');");

        db.execSQL("INSERT INTO tasks (id, project_id, group_id, title, description, created_by, status, priority, due_date) VALUES (1, 1, 1, 'Complete Project Proposal', 'Draft and submit the FYP proposal.', 6, 'Completed', 'High', '2026-09-30');");

        Log.d(TAG, "Database created and fully seeded.");
    }

    @Override
    public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
        db.execSQL("DROP TABLE IF EXISTS leader_votes;");
        db.execSQL("DROP TABLE IF EXISTS invitations;");
        db.execSQL("DROP TABLE IF EXISTS milestones;");
        db.execSQL("DROP TABLE IF EXISTS notifications;");
        db.execSQL("DROP TABLE IF EXISTS announcements;");
        db.execSQL("DROP TABLE IF EXISTS messages;");
        db.execSQL("DROP TABLE IF EXISTS files;");
        db.execSQL("DROP TABLE IF EXISTS tasks;");
        db.execSQL("DROP TABLE IF EXISTS group_members;");
        db.execSQL("DROP TABLE IF EXISTS groups;");
        db.execSQL("DROP TABLE IF EXISTS projects;");
        db.execSQL("DROP TABLE IF EXISTS course_enrollments;");
        db.execSQL("DROP TABLE IF EXISTS courses;");
        db.execSQL("DROP TABLE IF EXISTS students;");
        db.execSQL("DROP TABLE IF EXISTS teachers;");
        db.execSQL("DROP TABLE IF EXISTS users;");
        onCreate(db);
    }

    public String legacyReadFile(String sqlFilePath) {
        return "SQLite Database active and running.";
    }

    public String legacyGetData(String sqlFilePath, String searchTerm) {
        SQLiteDatabase db = this.getReadableDatabase();
        StringBuilder results = new StringBuilder();

        try {
            Cursor cursor = null;
            if (searchTerm.equals("ALL_PROJECTS")) {
                cursor = db.rawQuery("SELECT * FROM projects", null);
            } else if (searchTerm.equals("tasks")) {
                cursor = db.rawQuery("SELECT * FROM tasks", null);
            } else if (searchTerm.equals("teams")) {
                cursor = db.rawQuery("SELECT * FROM groups", null);
            } else if (searchTerm.equals("chat")) {
                cursor = db.rawQuery("SELECT u.username as sender, m.content as message FROM messages m JOIN users u ON m.sender_id = u.id", null);
            } else if (searchTerm.equals("files") || searchTerm.equals("shared_files")) {
                cursor = db.rawQuery("SELECT * FROM files", null);
            } else if (searchTerm.equals("student") || searchTerm.equals("teacher") || searchTerm.equals("admin") || searchTerm.equals("ALL_USERS")) {
                if (searchTerm.equals("ALL_USERS")) {
                    cursor = db.rawQuery("SELECT * FROM users", null);
                } else {
                    cursor = db.rawQuery("SELECT * FROM users WHERE role = ?", new String[]{searchTerm});
                }
            } else {
                // Search groups/teams first
                cursor = db.rawQuery("SELECT * FROM groups WHERE name LIKE ?", new String[]{"%" + searchTerm + "%"});
                if (cursor == null || !cursor.moveToFirst()) {
                    if (cursor != null) cursor.close();
                    // Fallback to tasks
                    cursor = db.rawQuery("SELECT * FROM tasks WHERE title LIKE ?", new String[]{"%" + searchTerm + "%"});
                    if (cursor == null || !cursor.moveToFirst()) {
                        if (cursor != null) cursor.close();
                        // Fallback to projects
                        cursor = db.rawQuery("SELECT * FROM projects WHERE name LIKE ?", new String[]{"%" + searchTerm + "%"});
                        if (cursor == null || !cursor.moveToFirst()) {
                            if (cursor != null) cursor.close();
                            // Fallback to users
                            cursor = db.rawQuery("SELECT * FROM users WHERE username LIKE ? OR email LIKE ?",
                                    new String[]{"%" + searchTerm + "%", "%" + searchTerm + "%"});
                        }
                    }
                }
            }

            if (cursor != null && cursor.moveToFirst()) {
                do {
                    if (searchTerm.equals("ALL_PROJECTS")) {
                        String name = cursor.getString(cursor.getColumnIndexOrThrow("name"));
                        String category = cursor.getString(cursor.getColumnIndexOrThrow("category"));
                        String status = cursor.getString(cursor.getColumnIndexOrThrow("status"));
                        int progress = cursor.getInt(cursor.getColumnIndexOrThrow("progress"));
                        results.append(name).append(" - ").append(category).append(" (").append(status).append(", ").append(progress).append("%)\n");
                    } else if (searchTerm.equals("tasks")) {
                        String title = cursor.getString(cursor.getColumnIndexOrThrow("title"));
                        String priority = cursor.getString(cursor.getColumnIndexOrThrow("priority"));
                        String status = cursor.getString(cursor.getColumnIndexOrThrow("status"));
                        results.append(title).append(" - ").append(priority).append(" Priority (").append(status).append(")\n");
                    } else if (searchTerm.equals("teams")) {
                        String name = cursor.getString(cursor.getColumnIndexOrThrow("name"));
                        String status = cursor.getString(cursor.getColumnIndexOrThrow("status"));
                        results.append(name).append(" - ").append(status).append("\n");
                    } else if (searchTerm.equals("chat")) {
                        String sender = cursor.getString(cursor.getColumnIndexOrThrow("sender"));
                        String message = cursor.getString(cursor.getColumnIndexOrThrow("message"));
                        results.append(sender).append(": ").append(message).append("\n");
                    } else if (searchTerm.equals("files") || searchTerm.equals("shared_files")) {
                        String fileName = cursor.getString(cursor.getColumnIndexOrThrow("file_name"));
                        String status = cursor.getString(cursor.getColumnIndexOrThrow("status"));
                        results.append(fileName).append(" [Status: ").append(status).append("]\n");
                    } else {
                        // Detect table type dynamically
                        boolean isTeam = false;
                        boolean isTask = false;
                        boolean isProject = false;
                        try {
                            cursor.getColumnIndexOrThrow("max_members");
                            isTeam = true;
                        } catch (IllegalArgumentException e0) {
                            try {
                                cursor.getColumnIndexOrThrow("priority");
                                isTask = true;
                            } catch (IllegalArgumentException e1) {
                                try {
                                    cursor.getColumnIndexOrThrow("category");
                                    isProject = true;
                                } catch (IllegalArgumentException e2) {
                                    isProject = false;
                                }
                            }
                        }

                        if (isTeam) {
                            String name = cursor.getString(cursor.getColumnIndexOrThrow("name"));
                            String status = cursor.getString(cursor.getColumnIndexOrThrow("status"));
                            int maxMembers = cursor.getInt(cursor.getColumnIndexOrThrow("max_members"));
                            int groupId = cursor.getInt(cursor.getColumnIndexOrThrow("id"));

                            // Query members for this group
                            StringBuilder membersBuilder = new StringBuilder();
                            Cursor memberCursor = db.rawQuery(
                                    "SELECT u.username, gm.role FROM group_members gm " +
                                            "JOIN students s ON gm.student_id = s.id " +
                                            "JOIN users u ON s.user_id = u.id " +
                                            "WHERE gm.group_id = ?", new String[]{String.valueOf(groupId)});
                            if (memberCursor != null && memberCursor.moveToFirst()) {
                                do {
                                    String uname = memberCursor.getString(memberCursor.getColumnIndexOrThrow("username"));
                                    String urole = memberCursor.getString(memberCursor.getColumnIndexOrThrow("role"));
                                    membersBuilder.append(uname).append(" (").append(urole).append(")\n");
                                } while (memberCursor.moveToNext());
                                memberCursor.close();
                            }

                            results.append("Name:").append(name).append("\n")
                                    .append("Status:").append(status).append("\n")
                                    .append("MaxMembers:").append(maxMembers).append("\n")
                                    .append("Members:\n").append(membersBuilder.toString());
                        } else if (isTask) {
                            String title = cursor.getString(cursor.getColumnIndexOrThrow("title"));
                            String status = cursor.getString(cursor.getColumnIndexOrThrow("status"));
                            String priority = cursor.getString(cursor.getColumnIndexOrThrow("priority"));
                            String dueDate = cursor.getString(cursor.getColumnIndexOrThrow("due_date"));
                            String description = cursor.getString(cursor.getColumnIndexOrThrow("description"));

                            results.append("Title:").append(title).append("\n")
                                    .append("Status:").append(status).append("\n")
                                    .append("Priority:").append(priority).append("\n")
                                    .append("DueDate:").append(dueDate).append("\n")
                                    .append("Description:").append(description).append("\n");
                        } else if (isProject) {
                            String name = cursor.getString(cursor.getColumnIndexOrThrow("name"));
                            String category = cursor.getString(cursor.getColumnIndexOrThrow("category"));
                            String status = cursor.getString(cursor.getColumnIndexOrThrow("status"));
                            int progress = cursor.getInt(cursor.getColumnIndexOrThrow("progress"));
                            String description = cursor.getString(cursor.getColumnIndexOrThrow("description"));
                            String periodStart = cursor.getString(cursor.getColumnIndexOrThrow("period_start"));
                            String periodEnd = cursor.getString(cursor.getColumnIndexOrThrow("period_end"));

                            results.append("Name:").append(name).append("\n")
                                    .append("Category:").append(category).append("\n")
                                    .append("Status:").append(status).append("\n")
                                    .append("Progress:").append(progress).append("\n")
                                    .append("Description:").append(description).append("\n")
                                    .append("Period:").append(periodStart).append(" to ").append(periodEnd).append("\n");
                        } else {
                            String username = cursor.getString(cursor.getColumnIndexOrThrow("username"));
                            String email = cursor.getString(cursor.getColumnIndexOrThrow("email"));
                            String passwordHash = cursor.getString(cursor.getColumnIndexOrThrow("password_hash"));
                            String role = cursor.getString(cursor.getColumnIndexOrThrow("role"));
                            results.append("User: ").append(username).append(" | Email: ").append(email).append(" | Password: ").append(passwordHash).append(" | Role: ").append(role).append("\n");
                        }
                    }
                } while (cursor.moveToNext());
            }
            if (cursor != null) {
                cursor.close();
            }

            if (results.length() == 0) {
                return "No matching records found for: " + searchTerm;
            }
        } catch (Exception e) {
            return "Error querying database: " + e.getMessage();
        }

        return results.toString();
    }

    public void legacyUpdateData(String sqlFilePath, String tableName, String updateQuery) {
        SQLiteDatabase db = this.getWritableDatabase();
        try {
            db.execSQL(updateQuery);
            Log.d(TAG, "[Adaptee] Successfully executed query on table [" + tableName + "]: " + updateQuery);
        } catch (Exception e) {
            Log.e(TAG, "[Adaptee] Failed to execute query: " + e.getMessage());
        }
    }
}
