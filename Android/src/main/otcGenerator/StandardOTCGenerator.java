package com.example.teambox_sk2.otcGenerator;

import java.util.Random;

public class StandardOTCGenerator implements OTCService {

    @Override
    public String generateCode() {
        // Generates a random 6-digit verification code (e.g., 482915)
        Random random = new Random();
        int code = 100000 + random.nextInt(900000);
        return String.valueOf(code);
    }

    @Override
    public boolean validateCode(String inputCode, String targetCode) {
        if (inputCode == null || targetCode == null) {
            return false;
        }
        return inputCode.trim().equals(targetCode.trim());
    }
}
