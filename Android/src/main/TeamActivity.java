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

public class TeamActivity extends AppCompatActivity {

    private Button btBack, btNewTeam;
    private ListView lvTeams;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_team);

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        initViews();
        initDatabase();
        loadTeamData();
        setupListeners();
    }

    private void initViews() {
        btBack = findViewById(R.id.btBack);
        btNewTeam = findViewById(R.id.btNewTeam);
        lvTeams = findViewById(R.id.lvTeams);
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
    }

    private void loadTeamData() {
        // Query groups/teams from database
        String teamResult = databaseAccessor.getData("teams");

        ArrayList<String> teamList = new ArrayList<>();
        if (teamResult != null && !teamResult.contains("No matching records found")) {
            String[] lines = teamResult.split("\n");
            teamList.addAll(Arrays.asList(lines));
        } else {
            teamList.add("Team 01 - Executing (Leader: CHAN Ho Yin)");
            teamList.add("Team 02 - Voting (Leader: Prof. Rebecca Lam)");
            teamList.add("Team ABC - Executing (Leader: CHAN Ho Yin)");
        }

        ArrayAdapter<String> adapter = new ArrayAdapter<>(
                this,
                android.R.layout.simple_list_item_1,
                teamList
        );
        lvTeams.setAdapter(adapter);
    }

    private void setupListeners() {
        btBack.setOnClickListener(v -> {
            Intent intent = new Intent(TeamActivity.this, StuHomeActivity.class);
            startActivity(intent);
            finish();
        });

        btNewTeam.setOnClickListener(v -> {
            Intent intent = new Intent(TeamActivity.this, CreateTeamActivity.class);
            startActivity(intent);
            finish();
        });

        lvTeams.setOnItemClickListener((parent, view, position, id) -> {
            String selectedItem = (String) parent.getItemAtPosition(position);

            if (selectedItem == null || selectedItem.isEmpty()) {
                return;
            }

            // Extract team name safely from format: "Team 01 - Executing..."
            String teamName = selectedItem;
            if (selectedItem.contains(" - ")) {
                teamName = selectedItem.split(" - ")[0].trim();
            }

            // Launch TeamViewActivity and pass the selected team name
            Intent intent = new Intent(TeamActivity.this, TeamViewActivity.class);
            intent.putExtra("TEAM_NAME", teamName);
            startActivity(intent);
        });
    }
}
