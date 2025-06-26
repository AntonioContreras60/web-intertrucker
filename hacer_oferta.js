let seleccionados = [];

// Función para agregar contactos a la lista de seleccionados sin duplicados
function agregarSeleccionado(id, nombre) {
    if (!seleccionados.some(contacto => contacto.id === id)) {
        seleccionados.push({ id: id, nombre: nombre });
        actualizarListaSeleccionados();
    }
}

// Función para actualizar la lista visual de seleccionados
function actualizarListaSeleccionados() {
    const listaSeleccionados = document.getElementById('listaSeleccionados');
    listaSeleccionados.innerHTML = '';

    seleccionados.forEach((item, index) => {
        const nuevoItem = document.createElement('div');
        nuevoItem.innerHTML = `${item.nombre} <button type='button' onclick='eliminarSeleccionado(${index})'>Quitar</button>`;
        listaSeleccionados.appendChild(nuevoItem);
    });

    // Actualizar el input oculto con los seleccionados en formato JSON
    document.getElementById('destinatarios_seleccionados').value = JSON.stringify(seleccionados);
}

// Función para eliminar un contacto de la lista seleccionada
function eliminarSeleccionado(index) {
    seleccionados.splice(index, 1);
    actualizarListaSeleccionados();
}

// Función para seleccionar un grupo y obtener sus contactos
function seleccionarGrupo(grupoId) {
    fetch('obtener_miembros_grupo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            'grupo_id': grupoId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error(data.error);
            return;
        }

        // Agregar cada miembro del grupo a la lista de seleccionados
        data.contactos.forEach(contacto => {
            agregarSeleccionado(contacto.id, contacto.nombre);
        });
    })
    .catch(error => console.error('Error al obtener los miembros del grupo:', error));
}
// Función para copiar el enlace al portapapeles
function copiarEnlace(idEnlace) {
    const inputElement = document.getElementById(idEnlace);

    if (inputElement) {
        const enlace = inputElement.value;

        navigator.clipboard.writeText(enlace).then(() => {
            alert('Enlace copiado al portapapeles');
        }).catch(err => {
            inputElement.select();
            document.execCommand('copy');
            alert('Enlace copiado al portapapeles mediante método alternativo');
        });
    } else {
        alert('Error: No se pudo encontrar el enlace para copiar.');
    }
}
