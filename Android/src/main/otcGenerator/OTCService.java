package com.example.teambox_sk2.otcGenerator;

public interface OTCService {
    String generateCode();
    boolean validateCode(String inputCode, String targetCode);
}
