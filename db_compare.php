<?php

$host1 = "localhost"; // database 1 host
$user1 = "user1"; // database 1 username
$password1 = "password1"; // database 1 password
$database1 = "database1"; // database 1 name

$host2 = "localhost"; // database 2 host
$user2 = "user2"; // database 2 username
$password2 = "password2"; // database 2 password
$database2 = "database2"; // database 2 name

// connect to database 1
$conn1 = mysqli_connect($host1, $user1, $password1, $database1);
if (!$conn1) {
    die("Connection failed: " . mysqli_connect_error());
}

// connect to database 2
$conn2 = mysqli_connect($host2, $user2, $password2, $database2);
if (!$conn2) {
    die("Connection failed: " . mysqli_connect_error());
}

// get a list of tables in database 1
$tables1 = array();
$result1 = mysqli_query($conn1, "SELECT table_name FROM information_schema.tables WHERE table_schema='$database1'");
if ($result1) {
    while ($row = mysqli_fetch_array($result1)) {
        $tables1[] = $row[0];
    }
}

// get a list of tables in database 2
$tables2 = array();
$result2 = mysqli_query($conn2, "SELECT table_name FROM information_schema.tables WHERE table_schema='$database2'");
if ($result2) {
    while ($row = mysqli_fetch_array($result2)) {
        $tables2[] = $row[0];
    }
}

// compare the lists of tables
$diff1 = array_diff($tables1, $tables2); // tables in database 1 but not in database 2
$diff2 = array_diff($tables2, $tables1); // tables in database 2 but not in database 1

if (!empty($diff1)) {
    echo "Tables in $database1 but not in $database2: " . implode(", ", $diff1) . "\n";
}

if (!empty($diff2)) {
    echo "Tables in $database2 but not in $database1: " . implode(", ", $diff2) . "\n";
}

// compare the structure of each table that exists in both databases
foreach ($tables1 as $table) {
    if (in_array($table, $tables2)) {
        $fields1 = array();
        $result1 = mysqli_query($conn1, "SHOW COLUMNS FROM $table");
        if ($result1) {
            while ($row = mysqli_fetch_array($result1)) {
                $fields1[] = $row[0];
            }
        }

        $fields2 = array();
        $result2 = mysqli_query($conn2, "SHOW COLUMNS FROM $table");
        if ($result2) {
            while ($row = mysqli_fetch_array($result2)) {
                $fields2[] = $row[0];
            }
        }

        $diff1 = array_diff($fields1, $fields2); // fields in table $table in database 1 but not in database 2
        $diff2 = array_diff($fields2, $fields1); // fields in table $table in database 2 but not in database 1

        if (!empty($diff1)) {
            echo "Fields in table $table in $database1 but not in $database2: " . implode(", ", $diff1) . "\n";
        }

        if (!empty($diff2)) {
            echo "Fields in table $table in $database2 but not in $database1: " . implode(", ", $diff2) . "\n";
        }
    }
}

// compare the data of each table that exists in both databases
foreach ($tables1 as $table) {
    if (in_array($table, $tables2)) {
        $result1 = mysqli_query($conn1, "SELECT * FROM $table");
        $result2 = mysqli_query($conn2, "SELECT * FROM $table");

        $rows1 = array();
        if ($result1) {
            while ($row = mysqli_fetch_assoc($result1)) {
                $rows1[] = $row;
            }
        }

        $rows2 = array();
        if ($result2) {
            while ($row = mysqli_fetch_assoc($result2)) {
                $rows2[] = $row;
            }
        }

        $diff1 = array();
        foreach ($rows1 as $row) {
            if (!in_array($row, $rows2)) {
                $diff1[] = $row;
            }
        }

        $diff2 = array();
        foreach ($rows2 as $row) {
            if (!in_array($row, $rows1)) {
                $diff2[] = $row;
            }
        }

        if (!empty($diff1)) {
            $inserts = array();
            foreach ($diff1 as $row) {
                $values = array();
                foreach ($row as $value) {
                    $values[] = "'" . mysqli_real_escape_string($conn2, $value) . "'";
                }
                $inserts[] = "(" . implode(", ", $values) . ")";
            }

            $insert_sql = "INSERT INTO $table VALUES " . implode(", ", $inserts) . ";";
            echo "Rows in table $table in $database1 but not in $database2: " . count($diff1) . "\n";
            file_put_contents("update.sql", $insert_sql . "\n", FILE_APPEND);
        }

        if (!empty($diff2)) {
            echo "Rows in table $table in $database2 but not in $database1: " . count($diff2) . "\n";
        }
    }
}
