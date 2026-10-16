package com.example.teambox_sk2.dbDefinder;

public class fyp03DBClassAdapter extends fyp03Adaptee implements fyp03DB {

    @Override
    public String readData(String sourceFile) {return legacyReadFile(sourceFile);}

    @Override
    public String getData(String criteria) {return legacyGetData("teambox.sql", criteria);}

    @Override
    public void updateData(String targetTable, String updateQuery) {legacyUpdateData("teambox.sql", targetTable, updateQuery);}
}
