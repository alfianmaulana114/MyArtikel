// Test setup file
beforeEach(() => {
    // Clear any mocks atau setup yang diperlukan
    jest.clearAllMocks();
});

// Mock network delays untuk testing
jest.setTimeout(10000); // 10 seconds

// Suppress console logs during tests unless explicitly needed
if (process.env.NODE_ENV === "test") {
    console.log = jest.fn();
    console.error = jest.fn();
    console.warn = jest.fn();
}
