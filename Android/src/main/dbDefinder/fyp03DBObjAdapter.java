package com.example.teambox_sk2.dbDefinder;

public class fyp03DBObjAdapter implements fyp03DB {
    private fyp03Adaptee adaptee;

    public fyp03DBObjAdapter(fyp03Adaptee adaptee) {this.adaptee = adaptee;}

    @Override
    public String readData(String sourceFile) {return adaptee.legacyReadFile(sourceFile);}

    @Override
    public String getData(String criteria) {return adaptee.legacyGetData("teambox.sql", criteria);}

    @Override
    public void updateData(String targetTable, String updateQuery) {adaptee.legacyUpdateData("teambox.sql", targetTable, updateQuery);}
}
