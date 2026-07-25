function adminApi(action, data) {
    return fetch('/mofaqdat/admin/api.php?action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json());
}
