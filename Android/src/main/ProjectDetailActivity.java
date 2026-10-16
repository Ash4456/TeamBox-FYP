package com.example.teambox_sk2;

import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;

public class ProjectDetailActivity extends AppCompatActivity {

    private Button btBack;
    private TextView tvProjectName, tvCategory, tvStatus, tvProgress, tvDescription, tvPeriod;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_project_detail);

        // Initialize database accessor via Factory
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");

        // Bind UI components
        btBack = findViewById(R.id.btBack);
        tvProjectName = findViewById(R.id.tvProjectName);
        tvCategory = findViewById(R.id.tvCategory);
        tvStatus = findViewById(R.id.tvStatus);
        tvProgress = findViewById(R.id.tvProgress);
        tvDescription = findViewById(R.id.tvDescription);
        tvPeriod = findViewById(R.id.tvPeriod);

        // Retrieve data passed from ProjectActivity
        String projectName = getIntent().getStringExtra("PROJECT_NAME");
        if (projectName == null || projectName.isEmpty()) {
            projectName = "TeamBox"; // Fallback default
        }

        // Load project information dynamically from database
        loadProjectDetails(projectName);

        // Back button listener
        btBack.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                finish();
            }
        });
    }

    private void loadProjectDetails(String projectName) {
        tvProjectName.setText(projectName);

        // Query database using the project name keyword search
        String result = databaseAccessor.getData(projectName);

        if (result != null && !result.contains("No matching records found") && result.contains("Category:")) {
            // Parse the structured text returned by the adapter
            String category = extractField(result, "Category:");
            String status = extractField(result, "Status:");
            String progress = extractField(result, "Progress:");
            String description = extractField(result, "Description:");
            String period = extractField(result, "Period:");

            tvCategory.setText("Category: " + (category.isEmpty() ? "General" : category));
            tvStatus.setText("Status: " + (status.isEmpty() ? "Recruiting" : status));
            tvProgress.setText("Progress: " + (progress.isEmpty() ? "0" : progress) + "%");
            tvDescription.setText(description.isEmpty() ? "No description provided." : description);
            tvPeriod.setText("Period: " + (period.isEmpty() ? "2026/27 Academic Year" : period));
        } else {
            // Fallback display if database query is empty or fails
            tvCategory.setText("Category: General");
            tvStatus.setText("Status: Recruiting");
            tvProgress.setText("Progress: 0%");
            tvDescription.setText("Details for project: " + projectName);
            tvPeriod.setText("Period: 2026/27 Academic Year");
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
