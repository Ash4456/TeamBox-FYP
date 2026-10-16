package com.example.teambox_sk2;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.example.teambox_sk2.dbDefinder.fyp03DB;
import com.example.teambox_sk2.dbDefinder.fyp03Factory;
import com.example.teambox_sk2.otcGenerator.OTCFactory;
import com.example.teambox_sk2.otcGenerator.OTCService;

import java.util.UUID;

public class MainActivity extends AppCompatActivity {
    private static final String TAG = "MainActivityLifecycle";

    // UI Elements
    private Button registBtn, btGet, btLogin, btChangePwd, btForgetPwd;
    private EditText uname, passwd, OTC;
    private TextView tvGeneratedCode;

    private fyp03DB databaseAccessor;
    private OTCService otcService;
    private String activeGeneratedCode = "";

    private String[] urs = {"admin", "teacher", "student"};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        // Set the Toolbar as the ActionBar so the options menu can render
        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);

        com.example.teambox_sk2.dbDefinder.fyp03Adaptee.initializeContext(this);

        initViews();
        initDatabase();
        initCodeGenerator();
        setupListeners();

        Log.d(TAG, "onCreate: MainActivity is created");
    }

    private void initViews() {
        registBtn = findViewById(R.id.registBtn);
        btGet = findViewById(R.id.btGet);
        btLogin = findViewById(R.id.btLogin);
        btChangePwd = findViewById(R.id.btChangePwd);
        btForgetPwd = findViewById(R.id.btForgetPwd);
        uname = findViewById(R.id.uname);
        passwd = findViewById(R.id.passwd);
        OTC = findViewById(R.id.OTC);
        tvGeneratedCode = findViewById(R.id.tvGeneratedCode);
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
        databaseAccessor.readData("teambox.sql");
    }

    private void initCodeGenerator() {
        otcService = OTCFactory.getOTCService("STANDARD");
    }

    private void setupListeners() {
        registBtn.setOnClickListener(v ->
                Toast.makeText(MainActivity.this, "Navigate to Registration page", Toast.LENGTH_SHORT).show()
        );

        btGet.setOnClickListener(v -> {
            String username = uname.getText().toString().trim();
            if (username.isEmpty()) {
                Toast.makeText(MainActivity.this, "Please enter your username/email first", Toast.LENGTH_SHORT).show();
            } else {
                activeGeneratedCode = otcService.generateCode();
                tvGeneratedCode.setText(activeGeneratedCode);
                Toast.makeText(MainActivity.this, "OTC Generated Successfully!", Toast.LENGTH_SHORT).show();
                Log.d(TAG, "Generated OTC for " + username + ": " + activeGeneratedCode);
            }
        });

        btLogin.setOnClickListener(v -> {
            String inputUser = uname.getText().toString().trim();
            String inputPass = passwd.getText().toString().trim();
            String inputOtc = OTC.getText().toString().trim();

            if (inputUser.isEmpty() || inputPass.isEmpty()) {
                Toast.makeText(MainActivity.this, "Username and password cannot be empty", Toast.LENGTH_SHORT).show();
                return;
            }

            if (!activeGeneratedCode.isEmpty() && !otcService.validateCode(inputOtc, activeGeneratedCode)) {
                Toast.makeText(MainActivity.this, "Invalid Verification Code (OTC). Please check the code displayed.", Toast.LENGTH_LONG).show();
                return;
            }

            boolean isAuthenticated = authenticateUser(inputUser, inputPass);

            if (isAuthenticated) {
                String userRole = getUserRole(inputUser);
                String sessionToken = generateAccountToken(inputUser);
                Toast.makeText(MainActivity.this, "Login Successful! Role: " + userRole, Toast.LENGTH_SHORT).show();

                if (userRole.equals(urs[0])) {
                    // TODO: create a admin main activity
                } else if (userRole.equals(urs[1])) {
                    // TODO: create a teacher main activity
                } else if (userRole.equals(urs[2])) {
                    Intent intent = new Intent(MainActivity.this, StuHomeActivity.class);
                    intent.putExtra("EXTRA_USERNAME", inputUser);
                    intent.putExtra("EXTRA_ROLE", userRole);
                    intent.putExtra("EXTRA_TOKEN", sessionToken);
                    intent.putExtra("EXTRA_LOGIN_TIME", System.currentTimeMillis());

                    startActivity(intent);
                    finish();
                } else {
                    Toast.makeText(MainActivity.this, "Undefined related role main activity", Toast.LENGTH_LONG).show();
                    Intent intent = new Intent(MainActivity.this, StartActivity.class);
                    startActivity(intent);
                    finish();
                }
            } else {
                Toast.makeText(MainActivity.this, "Invalid credentials or user not found in database.", Toast.LENGTH_LONG).show();
            }
        });

        btChangePwd.setOnClickListener(v -> {
            String user = uname.getText().toString().trim();
            if(!user.isEmpty()) {
                Intent intent = new Intent(MainActivity.this, ChangePasswordActivity.class);
                intent.putExtra("EXTRA_USERNAME", user);
                startActivity(intent);
                finish();
            } else {
                Toast.makeText(MainActivity.this, "Enter username to change password", Toast.LENGTH_SHORT).show();
            }
        });

        // FIXED: Launches ForgetPasswordActivity and optionally pre-fills the username if typed
        btForgetPwd.setOnClickListener(v -> {
            String user = uname.getText().toString().trim();
            Intent intent = new Intent(MainActivity.this, ForgetPasswordActivity.class);
            if (!user.isEmpty()) {
                intent.putExtra("EXTRA_USERNAME", user);
            }
            startActivity(intent);
        });
    }

    private boolean authenticateUser(String usernameOrEmail, String password) {
        String dbResult = databaseAccessor.getData(usernameOrEmail);
        boolean userExistsInDb = dbResult != null && !dbResult.contains("No matching records found");

        // Checks password against the actual database record
        boolean passwordMatches = dbResult != null && dbResult.contains(password);

        return userExistsInDb && passwordMatches;
    }

    private String getUserRole(String usernameOrEmail) {
        String dbResult = databaseAccessor.getData(usernameOrEmail);
        if (dbResult != null) {
            if (dbResult.contains("teacher")) {
                return "teacher";
            } else if (dbResult.contains("student")) {
                return "student";
            } else if (dbResult.contains("admin")) {
                return "admin";
            }
        }
        return "unknown";
    }

    private String generateAccountToken(String username) {
        String rawTokenString = username + "-" + UUID.randomUUID().toString() + "-" + System.currentTimeMillis();
        return android.util.Base64.encodeToString(rawTokenString.getBytes(), android.util.Base64.NO_WRAP);
    }

    // --- Options Menu Methods ---

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.menu_main_activity, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        int id = item.getItemId();

        if (id == R.id.action_menu) {
            Toast.makeText(this, "Action Menu clicked", Toast.LENGTH_SHORT).show();
            return true;
        } else if (id == R.id.action_privacy) {
            Toast.makeText(this, "Privacy Policy clicked", Toast.LENGTH_SHORT).show();
            return true;
        } else if (id == R.id.action_help) {
            Toast.makeText(this, "Help clicked", Toast.LENGTH_SHORT).show();
            return true;
        } else if (id == R.id.action_info) {
            Toast.makeText(this, "App Information clicked", Toast.LENGTH_SHORT).show();
            return true;
        }

        return super.onOptionsItemSelected(item);
    }

    // --- Lifecycle Methods ---
    @Override
    protected void onStart() { super.onStart(); Log.d(TAG, "onStart"); }
    @Override
    protected void onResume() { super.onResume(); Log.d(TAG, "onResume"); }
    @Override
    protected void onPause() { super.onPause(); Log.d(TAG, "onPause"); }
    @Override
    protected void onStop() { super.onStop(); Log.d(TAG, "onStop"); }
    @Override
    protected void onRestart() { super.onRestart(); Log.d(TAG, "onRestart"); }
    @Override
    protected void onDestroy() { super.onDestroy(); Log.d(TAG, "onDestroy"); }
}
