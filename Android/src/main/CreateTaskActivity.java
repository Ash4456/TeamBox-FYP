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

public class CreateTaskActivity extends AppCompatActivity {

    private EditText etTaskTitle, etTaskDesc;
    private Spinner spPriority;
    private Button btnTaskDeadline, btnCreateTask, btnTaskPage;
    private TextView tvTaskOutput;

    private fyp03DB databaseAccessor;
    private String selectedDueDate = "";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_create_task);

        // Apply edge-to-edge window insets
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        initViews();
        initDatabase();
        setupSpinner();
        setupListeners();
    }

    private void initViews() {
        etTaskTitle = findViewById(R.id.etTaskTitle);
        etTaskDesc = findViewById(R.id.etTaskDesc);
        spPriority = findViewById(R.id.spPriority);
        btnTaskDeadline = findViewById(R.id.btnTaskDeadline);
        btnCreateTask = findViewById(R.id.btnCreateTask);
        btnTaskPage = findViewById(R.id.btnTaskPage);
        tvTaskOutput = findViewById(R.id.tvTaskOutput);
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
    }

    private void setupSpinner() {
        String[] priorities = {"Low", "Medium", "High"};
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, priorities);
        spPriority.setAdapter(adapter);
    }

    private void setupListeners() {
        // Date Picker Dialog for Task Due Date Selection
        btnTaskDeadline.setOnClickListener(v -> {
            Calendar calendar = Calendar.getInstance();
            int year = calendar.get(Calendar.YEAR);
            int month = calendar.get(Calendar.MONTH);
            int day = calendar.get(Calendar.DAY_OF_MONTH);

            DatePickerDialog datePickerDialog = new DatePickerDialog(this, (view, selectedYear, selectedMonth, selectedDay) -> {
                selectedDueDate = selectedYear + "-" + (selectedMonth + 1) + "-" + selectedDay;
                btnTaskDeadline.setText("Due Date: " + selectedDueDate);
            }, year, month, day);
            datePickerDialog.show();
        });

        // Save Task Action: Validates input and executes DB insert query
        btnCreateTask.setOnClickListener(v -> {
            String title = etTaskTitle.getText().toString().trim();
            String desc = etTaskDesc.getText().toString().trim();
            String priority = spPriority.getSelectedItem().toString();

            if (title.isEmpty()) {
                etTaskTitle.setError("Task title is required");
                return;
            }

            if (selectedDueDate.isEmpty()) {
                Toast.makeText(this, "Please select a due date", Toast.LENGTH_SHORT).show();
                return;
            }

            // Execute insert query via dbDefinder adapter into SQLite
            String insertQuery = "INSERT INTO tasks (project_id, group_id, title, description, status, priority, due_date) VALUES (1, 1, '" + title + "', '" + desc + "', 'Pending', '" + priority + "', '" + selectedDueDate + "');";
            databaseAccessor.updateData("tasks", insertQuery);

            // Display confirmation summary
            String summary = "✅ Task Created Successfully!\n" +
                    "• Title: " + title + "\n" +
                    "• Priority: " + priority + "\n" +
                    "• Due Date: " + selectedDueDate + "\n" +
                    (!desc.isEmpty() ? "• Description: " + desc : "");
            tvTaskOutput.setText(summary);

            Toast.makeText(this, "Task saved to local database!", Toast.LENGTH_SHORT).show();
        });

        // Navigation back to TaskActivity list
        btnTaskPage.setOnClickListener(v -> {
            Intent intent = new Intent(CreateTaskActivity.this, TaskActivity.class);
            startActivity(intent);
            finish();
        });
    }
}
