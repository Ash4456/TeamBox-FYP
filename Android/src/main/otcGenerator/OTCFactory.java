package com.example.teambox_sk2.otcGenerator;

public class OTCFactory {

    public static OTCService getOTCService(String type) {
        if (type != null && type.equalsIgnoreCase("STANDARD")) {
            return new StandardOTCGenerator();
        }
        // Default fallback implementation
        return new StandardOTCGenerator();
    }
}
