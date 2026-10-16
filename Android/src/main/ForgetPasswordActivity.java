package com.example.teambox_sk2;

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

public class ForgetPasswordActivity extends AppCompatActivity {

    private static final String TAG = "ForgetPasswordActivity";

    private EditText etForgetUsername, etNewPassword;
    private Button btnResetPassword;
    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_forget_password);

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
        etForgetUsername = findViewById(R.id.etForgetUsername);
        etNewPassword = findViewById(R.id.etNewPassword);
        btnResetPassword = findViewById(R.id.btnResetPassword);

        // Pre-fill username if passed from MainActivity
        String passedUsername = getIntent().getStringExtra("EXTRA_USERNAME");
        if (passedUsername != null && !passedUsername.isEmpty()) {
            etForgetUsername.setText(passedUsername);
        }
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
        databaseAccessor.readData("teambox.sql");
    }

    private void setupListeners() {
        btnResetPassword.setOnClickListener(v -> {
            String username = etForgetUsername.getText().toString().trim();
            String newPassword = etNewPassword.getText().toString().trim();

            if (username.isEmpty() || newPassword.isEmpty()) {
                Toast.makeText(this, "Please fill in all fields", Toast.LENGTH_SHORT).show();
                return;
            }

            // Check if user exists in the database
            String dbResult = databaseAccessor.getData(username);
            boolean userExists = dbResult != null && !dbResult.contains("No matching records found");

            if (userExists) {
                // Execute SQL update command via database adapter to set the new password
                String updateQuery = "UPDATE users SET password_hash = '" + newPassword + "' WHERE username = '" + username + "';";
                databaseAccessor.updateData("users", updateQuery);

                Toast.makeText(this, "Password updated successfully! You can now log in.", Toast.LENGTH_LONG).show();
                Log.d(TAG, "Password successfully reset for user: " + username);

                finish(); // Return to login screen
            } else {
                Toast.makeText(this, "User not found in database", Toast.LENGTH_SHORT).show();
                Log.d(TAG, "Password reset failed: User not found -> " + username);
            }
        });
    }
}
