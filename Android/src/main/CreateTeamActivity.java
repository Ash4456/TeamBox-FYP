package com.example.teambox_sk2;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;

public class CreateTeamActivity extends AppCompatActivity {

    private Button btBack, btSubmit;
    private EditText etTeamName, etProjectId, etMaxMembers;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_team);

        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");

        initViews();
        setupListeners();
    }

    private void initViews() {
        btBack = findViewById(R.id.btBack);
        btSubmit = findViewById(R.id.btSubmit);
        etTeamName = findViewById(R.id.etTeamName);
        etProjectId = findViewById(R.id.etProjectId);
        etMaxMembers = findViewById(R.id.etMaxMembers);
    }

    private void setupListeners() {
        btBack.setOnClickListener(v -> navigateBack());

        btSubmit.setOnClickListener(v -> {
            String teamName = etTeamName.getText().toString().trim();
            String projectIdStr = etProjectId.getText().toString().trim();
            String maxMembersStr = etMaxMembers.getText().toString().trim();

            if (teamName.isEmpty() || projectIdStr.isEmpty()) {
                Toast.makeText(CreateTeamActivity.this, "Please fill in team name and project ID.", Toast.LENGTH_SHORT).show();
                return;
            }

            int projectId = Integer.parseInt(projectIdStr);
            int maxMembers = maxMembersStr.isEmpty() ? 4 : Integer.parseInt(maxMembersStr);

            // Construct SQL insert query matching your groups table structure
            String insertQuery = "INSERT INTO groups (project_id, name, max_members, status) VALUES (" +
                    projectId + ", '" + teamName + "', " + maxMembers + ", 'Recruiting');";

            try {
                databaseAccessor.updateData("groups", insertQuery);
                Toast.makeText(CreateTeamActivity.this, "Team created successfully!", Toast.LENGTH_SHORT).show();
                navigateBack();
            } catch (Exception e) {
                Toast.makeText(CreateTeamActivity.this, "Error creating team: " + e.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void navigateBack() {
        Intent intent = new Intent(CreateTeamActivity.this, TeamActivity.class);
        startActivity(intent);
        finish();
    }
}
