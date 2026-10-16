package com.example.teambox_sk2;

import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;

public class TeamViewActivity extends AppCompatActivity {

    private Button btBack;
    private TextView tvTeamName, tvTeamStatus, tvTeamMaxMembers, tvTeamMembers;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_team_view);

        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");

        btBack = findViewById(R.id.btBack);
        tvTeamName = findViewById(R.id.tvTeamName);
        tvTeamStatus = findViewById(R.id.tvTeamStatus);
        tvTeamMaxMembers = findViewById(R.id.tvTeamMaxMembers);
        tvTeamMembers = findViewById(R.id.tvTeamMembers);

        String teamName = getIntent().getStringExtra("TEAM_NAME");
        if (teamName == null || teamName.isEmpty()) {
            teamName = "Team 01";
        }

        loadTeamDetails(teamName);

        btBack.setOnClickListener(v -> finish());
    }

    private void loadTeamDetails(String teamName) {
        tvTeamName.setText(teamName);

        String result = databaseAccessor.getData(teamName);

        if (result != null && !result.contains("No matching records found") && result.contains("Status:")) {
            String status = extractField(result, "Status:");
            String maxMembers = extractField(result, "MaxMembers:");
            String members = extractField(result, "Members:");

            tvTeamStatus.setText("Status: " + (status.isEmpty() ? "Executing" : status));
            tvTeamMaxMembers.setText("Max Members: " + (maxMembers.isEmpty() ? "4" : maxMembers));
            tvTeamMembers.setText(members.isEmpty() ? "No members recorded." : members);
        } else {
            tvTeamStatus.setText("Status: Executing");
            tvTeamMaxMembers.setText("Max Members: 4");
            tvTeamMembers.setText("CHAN Ho Yin (Leader)\nCHUI Ka Chun (Member)\nLAI Ka-Yu Savio (Member)");
        }
    }

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
