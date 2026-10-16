package com.example.teambox_sk2;

import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;

public class TaskDetailActivity extends AppCompatActivity {

    private Button btBack;
    private TextView tvTaskTitle, tvTaskStatus, tvTaskPriority, tvTaskDescription, tvTaskDueDate;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_task_detail);

        // Initialize database accessor via Factory
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");

        // Bind UI components
        btBack = findViewById(R.id.btBack);
        tvTaskTitle = findViewById(R.id.tvTaskTitle);
        tvTaskStatus = findViewById(R.id.tvTaskStatus);
        tvTaskPriority = findViewById(R.id.tvTaskPriority);
        tvTaskDescription = findViewById(R.id.tvTaskDescription);
        tvTaskDueDate = findViewById(R.id.tvTaskDueDate);

        // Retrieve task title passed from previous activity
        String taskTitle = getIntent().getStringExtra("TASK_TITLE");
        if (taskTitle == null || taskTitle.isEmpty()) {
            taskTitle = "Complete Project Proposal"; // Fallback default
        }

        // Load task information dynamically from database
        loadTaskDetails(taskTitle);

        // Back button listener
        btBack.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                finish();
            }
        });
    }

    private void loadTaskDetails(String taskTitle) {
        tvTaskTitle.setText(taskTitle);

        // Query database using task title keyword search
        String result = databaseAccessor.getData(taskTitle);

        if (result != null && !result.contains("No matching records found") && result.contains("Status:")) {
            String status = extractField(result, "Status:");
            String priority = extractField(result, "Priority:");
            String dueDate = extractField(result, "DueDate:");
            String description = extractField(result, "Description:");

            tvTaskStatus.setText("Status: " + (status.isEmpty() ? "Pending" : status));
            tvTaskPriority.setText("Priority: " + (priority.isEmpty() ? "Medium" : priority));
            tvTaskDueDate.setText("Due Date: " + (dueDate.isEmpty() ? "N/A" : dueDate));
            tvTaskDescription.setText(description.isEmpty() ? "No description provided." : description);
        } else {
            // Fallback display
            tvTaskStatus.setText("Status: Pending");
            tvTaskPriority.setText("Priority: Medium");
            tvTaskDueDate.setText("Due Date: 2026-10-31");
            tvTaskDescription.setText("Details for task: " + taskTitle);
        }
    }

    // Helper method to parse specific fields from the raw query result string
    private String extractField(String data, String fieldKey) {
        try {
            int startIndex = data.indexOf(fieldKey);
            if (startIndex == -1) return "";
            startIndex += fieldKey.length();
            int endIndex = data.indexOf("\n", startIndex);
            if (endIndex == -1) endIndex = data.length();
            return data.substring(startIndex, endIndex).trim();
        } catch (Exception e) {
            return "";
        }
    }
}
