package com.example.teambox_sk2;

import android.app.DatePickerDialog;
import android.content.Intent;
import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;

import java.util.Calendar;

public class CreateProjectActivity extends AppCompatActivity {

    private EditText etTitle, etScore;
    private Spinner spGroupSize;
    private Button btnDeadline, btnCreate, btnStudentPage;
    private TextView tvOutput;

    private fyp03DB databaseAccessor;
    private String selectedDeadline = "";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_create_project);

        // Apply window insets for edge-to-edge support
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        initViews();    // Initialize UI components
        initDatabase(); // Initialize database accessor using dbDefinder pattern
        setupSpinner(); // Setup dropdown options for group size
        setupListeners();   // Setup click listeners and form actions
    }

    private void initViews() {
        etTitle = findViewById(R.id.etTitle);
        etScore = findViewById(R.id.etScore);
        spGroupSize = findViewById(R.id.spGroupSize);
        btnDeadline = findViewById(R.id.btnDeadline);
        btnCreate = findViewById(R.id.btnCreate);
        btnStudentPage = findViewById(R.id.btnStudentPage);
        tvOutput = findViewById(R.id.tvOutput);
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
    }

    private void setupSpinner() {
        String[] groupSizes = {"2 Members", "3 Members", "4 Members", "5 Members"};
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, groupSizes);
        spGroupSize.setAdapter(adapter);
    }

    private void setupListeners() {
        // Date Picker Dialog for Deadline Selection
        btnDeadline.setOnClickListener(v -> {
            Calendar calendar = Calendar.getInstance();
            int year = calendar.get(Calendar.YEAR);
            int month = calendar.get(Calendar.MONTH);
            int day = calendar.get(Calendar.DAY_OF_MONTH);

            DatePickerDialog datePickerDialog = new DatePickerDialog(this, (view, selectedYear, selectedMonth, selectedDay) -> {
                selectedDeadline = selectedYear + "-" + (selectedMonth + 1) + "-" + selectedDay;
                btnDeadline.setText("Deadline: " + selectedDeadline);
            }, year, month, day);
            datePickerDialog.show();
        });

        // Create Project Action: Validates form and saves to database
        btnCreate.setOnClickListener(v -> {
            String title = etTitle.getText().toString().trim();
            String scoreStr = etScore.getText().toString().trim();
            String groupSize = spGroupSize.getSelectedItem().toString();

            if (title.isEmpty()) {
                etTitle.setError("Project title is required");
                return;
            }

            if (selectedDeadline.isEmpty()) {
                Toast.makeText(this, "Please select a deadline date", Toast.LENGTH_SHORT).show();
                return;
            }

            // Execute insert query via dbDefinder adapter
            String insertQuery = "INSERT INTO projects (name, category, status, progress) VALUES ('" + title + "', 'Software Engineering', 'Recruiting', 0);";
            databaseAccessor.updateData("projects", insertQuery);

            // Display confirmation summary
            String summary = "✅ Project Created Successfully!\n" +
                    "• Title: " + title + "\n" +
                    "• Group Size: " + groupSize + "\n" +
                    "• Deadline: " + selectedDeadline + "\n" +
                    (!scoreStr.isEmpty() ? "• Target Score: " + scoreStr : "");
            tvOutput.setText(summary);

            Toast.makeText(this, "Project saved to local database!", Toast.LENGTH_SHORT).show();
        });

        // Student Page Action Button: Return to Student Dashboard
        btnStudentPage.setOnClickListener(v -> {
            Intent intent = new Intent(CreateProjectActivity.this, ProjectActivity.class);
            startActivity(intent);
            finish();
        });
    }
}
