<?php
$password = 'Admin123!';
$hash = '$2y$10$YQEzGODG5NQJbq3NiZC0duPFbKVXfnGOSz5hKN5bCLzFzklmDKR9O';

if (password_verify($password, $hash)) {
    echo "HASH IS CORRECT\n";
} else {
    echo "HASH IS INCORRECT\n";
    echo "New hash: " . password_hash($password, PASSWORD_DEFAULT) . "\n";
}
