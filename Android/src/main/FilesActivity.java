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

import java.util.ArrayList;

public class FilesActivity extends AppCompatActivity {

    private Button btBack, btUploadFile;
    private ListView lvMyFiles, lvSharedFiles;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_files);

        // Apply edge-to-edge window insets
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        initViews();
        loadFilesData();
        setupListeners();
    }

    private void initViews() {
        btBack = findViewById(R.id.btBack);
        btUploadFile = findViewById(R.id.btUploadFile);
        lvMyFiles = findViewById(R.id.lvMyFiles);
        lvSharedFiles = findViewById(R.id.lvSharedFiles);
    }

    private void loadFilesData() {
        // Group 1: Personal Files
        ArrayList<String> myFilesList = new ArrayList<>();
        myFilesList.add("FYP_Proposal_Draft.docx (2.4 MB) - Private");
        myFilesList.add("Research_Notes.pdf (1.1 MB) - Private");
        myFilesList.add("Architecture_Diagram.png (850 KB) - Private");

        ArrayAdapter<String> myAdapter = new ArrayAdapter<>(
                this,
                android.R.layout.simple_list_item_1,
                myFilesList
        );
        lvMyFiles.setAdapter(myAdapter);

        // Group 2: Shared Files with Recipient Info
        ArrayList<String> sharedFilesList = new ArrayList<>();
        sharedFilesList.add("TeamBox_Requirements.pdf\n↳ Shared with: CHUI Ka Chun (Group Member)");
        sharedFilesList.add("Database_Schema_v1.sql\n↳ Shared with: Esther Yuen (Teacher)");
        sharedFilesList.add("UI_Wireframes.fig\n↳ Shared with: CHAN Ho Yin (Group Member)");

        ArrayAdapter<String> sharedAdapter = new ArrayAdapter<>(
                this,
                android.R.layout.simple_list_item_1,
                sharedFilesList
        );
        lvSharedFiles.setAdapter(sharedAdapter);
    }

    private void setupListeners() {
        // Back Button: Return to Student Dashboard
        btBack.setOnClickListener(v -> {
            Intent intent = new Intent(FilesActivity.this, StuHomeActivity.class);
            startActivity(intent);
            finish();
        });

        // Upload Button Action
        btUploadFile.setOnClickListener(v ->
                Toast.makeText(FilesActivity.this, "Upload New Document clicked", Toast.LENGTH_SHORT).show()
        );

        // Personal file click listener
        lvMyFiles.setOnItemClickListener((parent, view, position, id) -> {
            String selected = (String) parent.getItemAtPosition(position);
            Toast.makeText(this, "Opening personal file: " + selected, Toast.LENGTH_SHORT).show();
        });

        // Shared file click listener
        lvSharedFiles.setOnItemClickListener((parent, view, position, id) -> {
            String selected = (String) parent.getItemAtPosition(position);
            Toast.makeText(this, "Viewing shared file details", Toast.LENGTH_SHORT).show();
        });
    }
}
