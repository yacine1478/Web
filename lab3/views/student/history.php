<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade History - Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Grade Manager - Student</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php?page=student.dashboard">Dashboard</a>
                <span class="nav-link text-light">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
                <a class="nav-link" href="index.php?page=logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Grade History</h2>
        <div id="historyContent">
            <div class="alert alert-info">Loading your grade history...</div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $.get('api/gpa.php', { action: 'history' }, function(data) {
                if (data.error) {
                    $('#historyContent').html('<div class="alert alert-warning">' + data.error + '</div>');
                    return;
                }

                if (data.history.length === 0) {
                    $('#historyContent').html('<div class="alert alert-info">No grade history available.</div>');
                    return;
                }

                var html = '<table class="table table-bordered">';
                html += '<thead><tr><th>Semester</th><th>Academic Year</th><th>GPA</th></tr></thead><tbody>';

                for (var i = 0; i < data.history.length; i++) {
                    var h = data.history[i];
                    html += '<tr>';
                    html += '<td>' + h.label + '</td>';
                    html += '<td>' + h.academic_year + '</td>';
                    html += '<td>' + (h.gpa !== null ? parseFloat(h.gpa).toFixed(2) : 'N/A') + '</td>';
                    html += '</tr>';
                }

                html += '</tbody></table>';
                $('#historyContent').html(html);
            }).fail(function() {
                $('#historyContent').html('<div class="alert alert-danger">Failed to load grade history.</div>');
            });
        });
    </script>
</body>
</html>