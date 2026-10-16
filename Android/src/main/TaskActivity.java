package com.example.teambox_sk2;

import android.content.Intent;
import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.ListView;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;

import java.util.ArrayList;
import java.util.Arrays;

public class TaskActivity extends AppCompatActivity {

    private Button btBack, btNewTask;
    private ListView lvTasks;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_task);

        // Apply system bar insets for edge-to-edge support
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        initViews();    // Initialize UI components
        initDatabase(); // Initialize database accessor using dbDefinder pattern
        loadTaskData(); // Load tasks data into the ListView
        setupListeners();   // Setup click listeners
    }

    private void initViews() {
        btBack = findViewById(R.id.btBack);
        btNewTask = findViewById(R.id.btNewTask);
        lvTasks = findViewById(R.id.lvTasks);
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
    }

    private void loadTaskData() {
        // Query the database context for tasks
        String taskResult = databaseAccessor.getData("tasks");

        ArrayList<String> taskList = new ArrayList<>();
        if (taskResult != null && !taskResult.contains("No matching records found")) {
            String[] lines = taskResult.split("\n");
            taskList.addAll(Arrays.asList(lines));
        } else {
            // Fallback seed items
            taskList.add("Complete Project Proposal - High Priority (Completed)");
            taskList.add("Requirement Analysis - Medium Priority (Completed)");
            taskList.add("Create System Interface - High Priority (In Progress)");
            taskList.add("Database Schema Design - Medium Priority (Pending)");
        }

        // Bind data to ListView using an ArrayAdapter
        ArrayAdapter<String> adapter = new ArrayAdapter<>(
                this,
                android.R.layout.simple_list_item_1,
                taskList
        );
        lvTasks.setAdapter(adapter);
    }

    private void setupListeners() {
        // Back Button: Return to Student Home Activity
        btBack.setOnClickListener(v -> {
            Intent intent = new Intent(TaskActivity.this, StuHomeActivity.class);
            startActivity(intent);
            finish();
        });

        // + New Button: Go to CreateTaskActivity
        btNewTask.setOnClickListener(v -> {
            Intent intent = new Intent(TaskActivity.this, CreateTaskActivity.class);
            startActivity(intent);
            finish();
        });

        // ListView Item Click Listener -> Launch TaskDetailActivity
        lvTasks.setOnItemClickListener((parent, view, position, id) -> {
            String selectedItem = (String) parent.getItemAtPosition(position);

            if (selectedItem == null || selectedItem.isEmpty()) {
                return;
            }

            // Extract the task title from format: "Complete Project Proposal - High Priority (Completed)"
            String taskTitle = selectedItem;
            if (selectedItem.contains(" - ")) {
                taskTitle = selectedItem.split(" - ")[0].trim();
            }

            // Launch TaskDetailActivity and pass the selected task title
            Intent intent = new Intent(TaskActivity.this, TaskDetailActivity.class);
            intent.putExtra("TASK_TITLE", taskTitle);
            startActivity(intent);
        });
    }
}
