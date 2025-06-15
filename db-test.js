const mysql = require('mysql2/promise');
require('dotenv').config();

async function testDatabaseConnection() {
    let connection;
    try {
        console.log('Testing database connection...');
        
        // Database configuration
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
        connection = await mysql.createConnection(dbConfig);
        
        console.log('✅ Database connection successful!');
        
        // Get list of tables
        const [tables] = await connection.execute('SHOW TABLES');
        const tableNames = tables.map(row => Object.values(row)[0]);
        console.log('📋 Available tables:', tableNames);
        
        // Check specific tables of interest
        const tablesToCheck = ['utilizatori', 'produse', 'categorii', 'comenzi'];
        
        for (const tableName of tablesToCheck) {
            if (tableNames.includes(tableName)) {
                // Get table structure
                const [columns] = await connection.execute(`DESCRIBE ${tableName}`);
                console.log(`\n📊 Table structure for '${tableName}':`);
                console.table(columns.map(col => ({
                    Field: col.Field,
                    Type: col.Type,
                    Null: col.Null,
                    Key: col.Key,
                    Default: col.Default
                })));
                
                // Count rows
                const [countResult] = await connection.execute(`SELECT COUNT(*) as count FROM ${tableName}`);
                const rowCount = countResult[0].count;
                console.log(`📈 Total rows in '${tableName}': ${rowCount}`);
                
                // Show sample data (limited to 5 rows)
                if (rowCount > 0) {
                    const [rows] = await connection.execute(`SELECT * FROM ${tableName} LIMIT 5`);
                    console.log(`📝 Sample data from '${tableName}':`);
                    console.table(rows);
                } else {
                    console.log(`⚠️ No data found in '${tableName}'`);
                }
            } else {
                console.log(`❌ Table '${tableName}' does not exist`);
            }
        }
        
    } catch (error) {
        console.error('❌ Database connection failed:', error.message);
        console.error('Error details:', error);
    } finally {
        // Close connection if it was established
        if (connection) {
            await connection.end();
            console.log('✅ Database connection closed successfully');
        }
    }
}

// Run the test
testDatabaseConnection();