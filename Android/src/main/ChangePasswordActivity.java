package com.example.teambox_sk2;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;

public class ChangePasswordActivity extends AppCompatActivity {

    private static final String TAG = "ChangePasswordActivity";

    private EditText etUsername, etCurrentPassword, etNewPassword;
    private Button btnSubmitChange;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_change_password);

        // Window insets listener for edge-to-edge support
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        // Setup Toolbar with Back Navigation Arrow
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowHomeEnabled(true);
        }
        toolbar.setNavigationOnClickListener(v -> onBackPressed());

        initViews();
        initDatabase();
        setupListeners();
    }

    private void initViews() {
        etUsername = findViewById(R.id.etUsername);
        etCurrentPassword = findViewById(R.id.etCurrentPassword);
        etNewPassword = findViewById(R.id.etNewPassword);
        btnSubmitChange = findViewById(R.id.btnSubmitChange);

        // Pre-fill username if passed from previous screen (e.g., MainActivity)
        String passedUsername = getIntent().getStringExtra("EXTRA_USERNAME");
        if (passedUsername != null && !passedUsername.isEmpty()) {
            etUsername.setText(passedUsername);
        }
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
        databaseAccessor.readData("teambox.sql");
    }

    private void setupListeners() {
        btnSubmitChange.setOnClickListener(v -> {
            String username = etUsername.getText().toString().trim();
            String currentPass = etCurrentPassword.getText().toString().trim();
            String newPass = etNewPassword.getText().toString().trim();

            if (username.isEmpty() || currentPass.isEmpty() || newPass.isEmpty()) {
                Toast.makeText(this, "Please fill in all fields", Toast.LENGTH_SHORT).show();
                return;
            }

            // Verify if user exists and current password matches (default seed password check)
            String dbResult = databaseAccessor.getData(username);
            boolean userExists = dbResult != null && !dbResult.contains("No matching records found");

            if (!userExists) {
                Toast.makeText(this, "User not found in database", Toast.LENGTH_SHORT).show();
                return;
            }

            // Simple validation matching your seed logic (default password is "12345")
            if (!currentPass.equals("12345")) {
                Toast.makeText(this, "Current password is incorrect", Toast.LENGTH_SHORT).show();
                return;
            }

            // Execute SQL update command via database adapter
            String updateQuery = "UPDATE users SET password_hash = '" + newPass + "' WHERE username = '" + username + "';";
            databaseAccessor.updateData("users", updateQuery);

            Toast.makeText(this, "Password updated successfully!", Toast.LENGTH_LONG).show();
            Log.d(TAG, "Password successfully updated for user: " + username);

            // Return to MainActivity or finish
            finish();
        });
    }
}
