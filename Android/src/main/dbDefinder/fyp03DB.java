package com.example.teambox_sk2.dbDefinder;
public interface fyp03DB {
    String readData(String sourceFile);
    String getData(String criteria);
    void updateData(String targetTable, String updateQuery);
}
