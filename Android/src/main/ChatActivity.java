package com.example.teambox_sk2;

import android.content.Intent;
import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
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

public class ChatActivity extends AppCompatActivity {

    private Button btBack, btnSendMessage;
    private EditText etMessageInput;
    private ListView lvChatMessages;

    private fyp03DB databaseAccessor;
    private ArrayList<String> messageList;
    private ArrayAdapter<String> chatAdapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_chat);

        // Apply system bar insets for edge-to-edge support
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        initViews();
        initDatabase();
        loadChatMessages();
        setupListeners();
    }

    private void initViews() {
        btBack = findViewById(R.id.btBack);
        btnSendMessage = findViewById(R.id.btnSendMessage);
        etMessageInput = findViewById(R.id.etMessageInput);
        lvChatMessages = findViewById(R.id.lvChatMessages);
    }

    private void initDatabase() {
        databaseAccessor = fyp03Factory.getDatabaseAdapter("OBJECT");
    }

    private void loadChatMessages() {
        // Query database context for chat messages
        String chatResult = databaseAccessor.getData("chat");

        messageList = new ArrayList<>();
        if (chatResult != null && !chatResult.contains("No matching records found")) {
            String[] lines = chatResult.split("\n");
            messageList.addAll(Arrays.asList(lines));
        } else {
            // Fallback seed messages if database table is empty
            messageList.add("Esther Yuen: Welcome to the TeamBox project channel.");
            messageList.add("CHAN Ho Yin: Thanks! Working on the architecture setup.");
            messageList.add("CHUI Ka Chun: Database schema is ready.");
        }

        chatAdapter = new ArrayAdapter<>(
                this,
                android.R.layout.simple_list_item_1,
                messageList
        );
        lvChatMessages.setAdapter(chatAdapter);
    }

    private void setupListeners() {
        // Back Button: Return to Student Dashboard
        btBack.setOnClickListener(v -> {
            Intent intent = new Intent(ChatActivity.this, StuHomeActivity.class);
            startActivity(intent);
            finish();
        });

        // Send Message Action
        btnSendMessage.setOnClickListener(v -> {
            String messageText = etMessageInput.getText().toString().trim();

            if (messageText.isEmpty()) {
                Toast.makeText(this, "Message cannot be empty", Toast.LENGTH_SHORT).show();
                return;
            }

            String formattedMessage = "You: " + messageText;

            // Execute insert query via dbDefinder adapter into SQLite chat table (if table exists)
            String insertQuery = "INSERT INTO chat (sender, message) VALUES ('You', '" + messageText + "');";
            databaseAccessor.updateData("chat", insertQuery);

            // Update local adapter list instantly
            messageList.add(formattedMessage);
            chatAdapter.notifyDataSetChanged();
            lvChatMessages.smoothScrollToPosition(messageList.size() - 1);

            // Clear input box
            etMessageInput.setText("");
        });
    }
}
