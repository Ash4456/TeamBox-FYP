package com.example.teambox_sk2;

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

public class MemberInviteActivity extends AppCompatActivity {

    private EditText etMemberEmail;
    private Spinner spMemberRole;
    private Button btnSendInvite, btnTeamPage;
    private TextView tvInviteOutput;

    private fyp03DB databaseAccessor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_member_invite);

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
        etMemberEmail = findViewById(R.id.etMemberEmail);
        spMemberRole = findViewById(R.id.spMemberRole);
        btnSendInvite = findViewById(R.id.btnSendInvite);
        btnTeamPage = findViewById(R.id.btnTeamPage);
        tvInviteOutput = findViewById(R.id.tvInviteOutput);
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
    }

    private void setupSpinner() {
        String[] roles = {"student", "teacher", "leader"};
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, roles);
        spMemberRole.setAdapter(adapter);
    }

    private void setupListeners() {
        // Send Invitation Action: Validates email and adds user/member record to the database
        btnSendInvite.setOnClickListener(v -> {
            String email = etMemberEmail.getText().toString().trim();
            String role = spMemberRole.getSelectedItem().toString();

            if (email.isEmpty()) {
                etMemberEmail.setError("Member email is required");
                return;
            }

            // Derive a username prefix from email for demo storage
            String username = email.contains("@") ? email.substring(0, email.indexOf("@")) : "NewMember";

            // Execute insert query via dbDefinder adapter into SQLite users table
            String insertQuery = "INSERT INTO users (username, email, password_hash, role) VALUES ('" + username + "', '" + email + "', '12345', '" + role + "');";
            databaseAccessor.updateData("users", insertQuery);

            // Display confirmation summary
            String summary = "✅ Invitation Sent Successfully!\n" +
                    "• Email: " + email + "\n" +
                    "• Assigned Role: " + role + "\n" +
                    "• Status: Added to local database";
            tvInviteOutput.setText(summary);

            Toast.makeText(this, "Member added to local database!", Toast.LENGTH_SHORT).show();
        });

        // Navigation back to TeamActivity list
        btnTeamPage.setOnClickListener(v -> {
            Intent intent = new Intent(MemberInviteActivity.this, TeamActivity.class);
            startActivity(intent);
            finish();
        });
    }
}
