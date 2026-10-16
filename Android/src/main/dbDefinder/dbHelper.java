package com.example.teambox_sk2.dbDefinder;

public class dbHelper {
    public static void main(String[] args) {
        System.out.println("=== Starting dbHelper Runner Driver ===");

        // 1. Instantiate Object Adapter via Factory
        fyp03DB dbObj = fyp03Factory.getDatabaseAdapter("OBJECT");

        // Receive & Read data from teambox.sql
        System.out.println("\n[Action] Reading/Receiving data from teambox.sql...");
        String fileContent = dbObj.readData("teambox.sql");
        if (fileContent != null && !fileContent.startsWith("Error")) {
            System.out.println("Successfully loaded teambox.sql (Length: " + fileContent.length() + " chars).");
        } else {
            System.out.println(fileContent);
        }

        // Get specific data entries from teambox.sql (Searching for teacher record from users table)
        System.out.println("\n[Action] Getting specific data (Searching for 'Esther Yuen'):");
        String searchResult = dbObj.getData("Esther Yuen");
        System.out.println(searchResult);

        // Test querying all projects using the expanded schema support
        System.out.println("\n[Action] Querying all projects:");
        String allProjects = dbObj.getData("ALL_PROJECTS");
        System.out.println(allProjects);

        // Update data / write back change
        System.out.println("\n[Action] Writing and Updating data in table 'users':");
        dbObj.updateData("users", "UPDATE users SET email = 'wyyuen_new@vtc.edu.hk' WHERE id = 1;");

        // 2. Test Class Adapter via Factory
        System.out.println("\n=== Testing Class Adapter Implementation ===");
        fyp03DB dbClass = fyp03Factory.getDatabaseAdapter("CLASS");

        System.out.println("\n[Action] Getting project record for 'TeamBox':");
        String projectRecord = dbClass.getData("TeamBox");
        System.out.println(projectRecord);

        // Query team chat logs using the updated database functions
        System.out.println("\n[Action] Fetching recent team chat messages:");
        String chatLogs = dbClass.getData("chat");
        System.out.println(chatLogs);

        // Query tasks using the updated database functions
        System.out.println("\n[Action] Fetching project tasks:");
        String tasksList = dbClass.getData("tasks");
        System.out.println(tasksList);

        System.out.println("\n[Action] Updating progress in table 'projects':");
        dbClass.updateData("projects", "UPDATE projects SET progress = 75 WHERE id = 1;");

        System.out.println("\n=== dbHelper Execution Completed Successfully ===");
    }
}
