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

public class ProjectActivity extends AppCompatActivity {

    private Button btBack, btNew;
    private ListView lvProjects;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_project);

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        initViews();
        initDatabase();
        loadProjectData();
        setupListeners();
    }

    private void initViews() {
        btBack = findViewById(R.id.btBack);
        btNew = findViewById(R.id.btNew);
        lvProjects = findViewById(R.id.lvProjects);
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
    }

    private void loadProjectData() {
        // Fetch all projects dynamically from the local database adapter
        String projectResult = databaseAccessor.getData("ALL_PROJECTS");

        ArrayList<String> projectList = new ArrayList<>();
        if (projectResult != null && !projectResult.contains("No matching records found")) {
            String[] lines = projectResult.split("\n");
            projectList.addAll(Arrays.asList(lines));
        } else {
            projectList.add("No projects found in database.");
        }

        ArrayAdapter<String> adapter = new ArrayAdapter<>(
                this,
                android.R.layout.simple_list_item_1,
                projectList
        );
        lvProjects.setAdapter(adapter);
    }

    private void setupListeners() {
        btBack.setOnClickListener(v -> {
            Intent intent = new Intent(ProjectActivity.this, StuHomeActivity.class);
            startActivity(intent);
            finish();
        });

        // Click 'New' to navigate to CreateProjectActivity
        btNew.setOnClickListener(v -> {
            Intent intent = new Intent(ProjectActivity.this, CreateProjectActivity.class);
            startActivity(intent);
            finish();
        });

        lvProjects.setOnItemClickListener((parent, view, position, id) -> {
            String selectedItem = (String) parent.getItemAtPosition(position);

            if (selectedItem.equals("No projects found in database.")) {
                return;
            }

            // Extract project name from format: "TeamBox - Software Engineering (Executing, 60%)"
            String projectName = selectedItem;
            if (selectedItem.contains(" - ")) {
                projectName = selectedItem.split(" - ")[0].trim();
            }

            // Launch ProjectDetailActivity and pass the selected project name
            Intent intent = new Intent(ProjectActivity.this, ProjectDetailActivity.class);
            intent.putExtra("PROJECT_NAME", projectName);
            startActivity(intent);
        });
    }
}
