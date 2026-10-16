package com.example.teambox_sk2;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.widget.ImageButton;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;

public class StuHomeActivity extends AppCompatActivity {

    private static final String TAG = "StuHomeActivityLifecycle";

    // UI Elements
    private TextView welBackMsg, tvActiveProjects, tvNotifications, tvAssignedTasks, tvCompletedTasks, tvProgressText, tvProgressPercentage;
    private ProgressBar progressBar;
    private ImageButton ibViewProjects, ibViewTasks, ibViewTeams, ibSD, ibChat;

    // Database accessor
    private fyp03DB databaseAccessor;
    private String currentUsername = "";
    private String userRole = "";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_stu_home);

        // Edge-to-edge window insets listener
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        // Set the Toolbar as the ActionBar so the options menu can render
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);

        extractIntentData();            // Extract Intent Extras passed from previous activity
        initViews();                    // Initialize UI components
        initDatabase();                 // Initialize database via dbDefinder package
        setupQuickAccessListeners();    // Setup quick access action button click listeners

        Log.d(TAG, "onCreate: StuHomeActivity created for user: " + currentUsername);
    }

    @Override
    protected void onResume() {
        super.onResume();
        // Automatically reload dashboard metrics every time the activity comes back to focus
        loadDashboardMetrics();
        Log.d(TAG, "onResume: StuHomeActivity has user focus, metrics refreshed.");
    }

    private void extractIntentData() {
        Intent intent = getIntent();
        if (intent != null) {
            currentUsername = intent.getStringExtra("EXTRA_USERNAME");
            userRole = intent.getStringExtra("EXTRA_ROLE");
            String token = intent.getStringExtra("EXTRA_TOKEN");
            long loginTime = intent.getLongExtra("EXTRA_LOGIN_TIME", 0);

            Log.d(TAG, "Logged in User: " + currentUsername + " | Role: " + userRole);
        }
        if (currentUsername == null || currentUsername.isEmpty()) {
            currentUsername = "Student";
        }
    }

    private void initViews() {
        welBackMsg = findViewById(R.id.wel_back_msg);
        tvActiveProjects = findViewById(R.id.textView4);
        tvNotifications = findViewById(R.id.textView7);
        tvAssignedTasks = findViewById(R.id.textView10);
        tvCompletedTasks = findViewById(R.id.textView13);
        tvProgressPercentage = findViewById(R.id.textView17);
        tvProgressText = findViewById(R.id.textView18);
        progressBar = findViewById(R.id.progressBar2);

        ibViewProjects = findViewById(R.id.ibViewProjects);
        ibViewTasks = findViewById(R.id.ibViewTasks);
        ibViewTeams = findViewById(R.id.ibViewTeams);
        ibSD = findViewById(R.id.ibSD);
        ibChat = findViewById(R.id.ibChat);

        // Customize welcome message with user's name
        welBackMsg.setText("Welcome back, " + currentUsername + "!");
    }

    private void initDatabase() {
        // Instantiate Object Adapter via Factory pattern from dbDefinder package
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
        databaseAccessor.readData("teambox.sql");
    }

    private void loadDashboardMetrics() {
        // 1. Query active projects dynamically from adapter
        String projectData = databaseAccessor.getData("ALL_PROJECTS");
        int activeProjectCount = 0;
        if (projectData != null && !projectData.contains("No matching records found")) {
            String[] lines = projectData.split("\n");
            for (String line : lines) {
                if (!line.trim().isEmpty()) {
                    activeProjectCount++;
                }
            }
        }
        if (activeProjectCount == 0) activeProjectCount = 2; // Fallback default

        // 2. Query tasks dynamically from adapter
        String taskData = databaseAccessor.getData("tasks");
        int totalTasksCount = 0;
        int completedTasksCount = 0;
        if (taskData != null && !taskData.contains("No matching records found")) {
            String[] lines = taskData.split("\n");
            for (String line : lines) {
                if (!line.trim().isEmpty()) {
                    totalTasksCount++;
                    if (line.contains("Completed")) {
                        completedTasksCount++;
                    }
                }
            }
        }
        if (totalTasksCount == 0) {
            totalTasksCount = 4;
            completedTasksCount = 2;
        }

        // Update UI metric views
        tvActiveProjects.setText(String.valueOf(activeProjectCount));
        tvNotifications.setText("3"); // Or query notifications count if table accessor exists
        tvAssignedTasks.setText(String.valueOf(totalTasksCount));
        tvCompletedTasks.setText(String.valueOf(completedTasksCount));

        // Calculate and display progress bar percentage
        int progressPercent = totalTasksCount > 0 ? (int) (((float) completedTasksCount / totalTasksCount) * 100) : 0;

        progressBar.setMax(100);
        progressBar.setProgress(progressPercent);
        tvProgressPercentage.setText(progressPercent + "%");
        tvProgressText.setText(completedTasksCount + " of " + totalTasksCount + " project tasks have been completed.");
    }

    private void setupQuickAccessListeners() {
        ibViewProjects.setOnClickListener(v -> {
            Toast.makeText(StuHomeActivity.this, "Opening Projects...", Toast.LENGTH_SHORT).show();
            Intent intent = new Intent(StuHomeActivity.this, ProjectActivity.class);
            startActivity(intent);
        });

        ibViewTasks.setOnClickListener(v -> {
            Toast.makeText(StuHomeActivity.this, "Opening My Tasks...", Toast.LENGTH_SHORT).show();
            Intent intent = new Intent(StuHomeActivity.this, TaskActivity.class);
            startActivity(intent);
        });

        ibViewTeams.setOnClickListener(v -> {
            Toast.makeText(StuHomeActivity.this, "Opening Teams / Groups...", Toast.LENGTH_SHORT).show();
            Intent intent = new Intent(StuHomeActivity.this, TeamActivity.class);
            startActivity(intent);
        });

        ibSD.setOnClickListener(v -> {
            Toast.makeText(StuHomeActivity.this, "Opening Files & Documents...", Toast.LENGTH_SHORT).show();
            Intent intent = new Intent(StuHomeActivity.this, FilesActivity.class);
            startActivity(intent);
        });

        ibChat.setOnClickListener(v -> {
            Toast.makeText(StuHomeActivity.this, "Opening Chat...", Toast.LENGTH_SHORT).show();
            Intent intent = new Intent(StuHomeActivity.this, ChatActivity.class);
            startActivity(intent);
        });
    }

    // --- Options Menu Methods ---

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.menu_stu_home_activity, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        int id = item.getItemId();

        if (id == R.id.action_notify) {
            Toast.makeText(this, "Notifications clicked", Toast.LENGTH_SHORT).show();
            return true;
        } else if (id == R.id.action_calendar) {
            Toast.makeText(this, "Calendar clicked", Toast.LENGTH_SHORT).show();
            return true;
        } else if (id == R.id.action_profile) {
            Toast.makeText(this, "Opening Student Profile...", Toast.LENGTH_SHORT).show();
            return true;
        } else if (id == R.id.action_logout) {
            Toast.makeText(this, "Logging out...", Toast.LENGTH_SHORT).show();
            Intent intent = new Intent(StuHomeActivity.this, MainActivity.class);
            startActivity(intent);
            finish();
            return true;
        } else if (id == R.id.action_projects) {
            startActivity(new Intent(StuHomeActivity.this, ProjectActivity.class));
            return true;
        } else if (id == R.id.action_tasks) {
            startActivity(new Intent(StuHomeActivity.this, TaskActivity.class));
            return true;
        } else if (id == R.id.action_files) {
            startActivity(new Intent(StuHomeActivity.this, FilesActivity.class));
            return true;
        } else if (id == R.id.action_teams) {
            startActivity(new Intent(StuHomeActivity.this, TeamActivity.class));
            return true;
        } else if (id == R.id.action_chat) {
            startActivity(new Intent(StuHomeActivity.this, ChatActivity.class));
            return true;
        } else if (id == R.id.action_setting) {
            Toast.makeText(this, "Settings clicked", Toast.LENGTH_SHORT).show();
            return true;
        }

        return super.onOptionsItemSelected(item);
    }

    // --- Lifecycle Methods ---
    @Override
    protected void onStart() {
        super.onStart();
        Log.d(TAG, "onStart: StuHomeActivity is visible");
    }

    @Override
    protected void onPause() {
        super.onPause();
        Log.d(TAG, "onPause: StuHomeActivity is losing focus");
    }

    @Override
    protected void onStop() {
        super.onStop();
        Log.d(TAG, "onStop: StuHomeActivity is no longer visible");
    }

    @Override
    protected void onRestart() {
        super.onRestart();
        Log.d(TAG, "onRestart: StuHomeActivity is restarting");
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        Log.d(TAG, "onDestroy: StuHomeActivity is being destroyed");
    }
}
