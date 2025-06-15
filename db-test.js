const mysql = require('mysql2/promise');
require('dotenv').config();

async function testDatabaseConnection() {
    try {
        console.log('Testing database connection...');
        
        // Database configuration - you'll need to update these values
        const dbConfig = {
            host: process.env.DB_HOST || 'localhost',
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            database: process.env.DB_NAME || 'gusturi_romanesti',
            port: process.env.DB_PORT || 3306
        };
        
        console.log('Connecting to database with config:', {
            host: dbConfig.host,
            user: dbConfig.user,
            database: dbConfig.database,
            port: dbConfig.port
        });
        
        // Create connection
        const connection = await mysql.createConnection(dbConfig);
        
        console.log('✅ Database connection successful!');
        
        // Test a simple query
        const [rows] = await connection.execute('SELECT 1 as test');
        console.log('✅ Test query successful:', rows);
        
        // Test if tables exist
        const [tables] = await connection.execute('SHOW TABLES');
        console.log('📋 Available tables:', tables.map(row => Object.values(row)[0]));
        
        // Close connection
        await connection.end();
        console.log('✅ Database connection closed successfully');
        
    } catch (error) {
        console.error('❌ Database connection failed:', error.message);
        console.error('Error details:', error);
    }
}

// Run the test
testDatabaseConnection();