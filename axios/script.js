document.addEventListener('DOMContentLoaded', function () {
    
    fetchData();

    document.getElementById('crudForm').addEventListener('submit', function (e) {
        
        e.preventDefault();

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;

        addData({ name, email });

    });
});

function fetchData() {
    axios.get('crud.php')
        .then(response => {
            const dataBody = document.getElementById('dataBody');
            dataBody.innerHTML = '';
            response.data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.name}</td>
                    <td>${row.email}</td>
                    <td><button onclick="deleteData(${row.id})">Delete</button></td>
                `;
                dataBody.appendChild(tr);
            });
        })
        .catch(error => {
            console.error('axios fetchData エラー', error);
        });
}

function addData(data) {
    axios.post('crud.php', data)
        .then(response => {
            document.getElementById('message').innerText = response.data.message;
            fetchData();
        })
        .catch(error => {
            console.error('axios addData エラー', error)
        });
}

function deleteData(id) {
    axios.delete(`crud.php?id=${id}`)
        .then(response => {
            document.getElementById('message').innerText = response.data.message;
            fetchData();
        })
        .catch(error => {
            console.error('axios deleteData エラー', error);
        });
}