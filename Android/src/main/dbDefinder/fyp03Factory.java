package com.example.teambox_sk2.dbDefinder;

public class fyp03Factory {

    public static fyp03DB getDatabaseAdapter(String type) {

        if (type == null) {return null;}

        if (type.equalsIgnoreCase("OBJECT")) {
            return new fyp03DBObjAdapter(new fyp03Adaptee());
        } else if (type.equalsIgnoreCase("CLASS")) {
            return new fyp03DBClassAdapter();
        }

        return null;
    }
}
