// js/cliente.js

let productos = [];
let carrito = [];
let seleccionesTemporales = {};

document.addEventListener("DOMContentLoaded", async () => {
  await cargarProductos();
  configurarEventos();
});

async function cargarProductos() {
  try {
    productos = await api.getProducts();

    // Simulamos un pequeño retraso (1 seg) para que se vea el Skeleton Loader
    setTimeout(() => {
      renderCategorias();
      renderPopulares();
      renderProductosLista(productos);
    }, 800);
  } catch (error) {
    console.error("Error real de la API:", error); // <-- Esto nos dirá en consola por qué falló

    const contenedor = document.getElementById("productos-lista");
    if (contenedor) {
      contenedor.innerHTML =
        '<p class="vacio">Error al cargar el menú. Verifica tu conexión.</p>';
    } else {
      // Si no encuentra el contenedor, avisa en la consola
      console.error("No se encontró el div 'productos-lista' en el HTML.");
    }
    showToast("No se pudo cargar el menú", "error");
  }
}

// 1. RENDERIZAR CATEGORÍAS CON ICONOS
function renderCategorias() {
  const categorias = [
    ...new Set(productos.map((p) => p.category).filter(Boolean)),
  ];
  const contenedor = document.getElementById("categorias");

  // Diccionario de iconos por categoría
  const iconos = {
    Bebidas: "☕",
    Pastelería: "🍰",
    Panadería: "🥐",
    General: "🍽️",
  };

  contenedor.innerHTML =
    `
        <div class="cat-item active" data-categoria="todas">
            <span>✨</span> Todas
        </div>
    ` +
    categorias
      .map(
        (cat) => `
        <div class="cat-item" data-categoria="${cat}">
            <span>${iconos[cat] || "🍽️"}</span> ${cat}
        </div>
    `,
      )
      .join("");

  contenedor.addEventListener("click", (e) => {
    const btn = e.target.closest(".cat-item");
    if (!btn) return;

    contenedor
      .querySelectorAll(".cat-item")
      .forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");

    const categoria = btn.dataset.categoria;
    const filtrados =
      categoria === "todas"
        ? productos
        : productos.filter((p) => p.category === categoria);

    renderProductosLista(filtrados);
  });
}

// 2. RENDERIZAR POPULARES
function renderPopulares() {
  const populares = productos.slice(0, 4);
  const contenedor = document.getElementById("populares");

  contenedor.innerHTML = populares
    .map(
      (p) => `
        <div class="popular-card" onclick="abrirDesdePopulares(${p.id})">
            <img src="img/${p.id}.jpg" alt="${p.name}" onerror="this.src='https://placehold.co/400x400/2c1e17/ffffff?text=Cafe'">
            <div class="popular-info">
                <h4>${p.name}</h4>
                <div class="precio">${formatPrice(p.price)}</div>
            </div>
        </div>
    `,
    )
    .join("");
}

function abrirDesdePopulares(productId) {
  // Hacer scroll hacia la lista principal
  document
    .getElementById("productos-lista")
    .scrollIntoView({ behavior: "smooth" });

  // Abrir las opciones del producto después de un pequeño delay
  setTimeout(() => {
    // Cerrar otros
    document
      .querySelectorAll(".producto-card")
      .forEach((c) => c.classList.remove("open"));
    toggleOpciones(productId);
  }, 500);
}

// 3. RENDERIZAR LISTA DE PRODUCTOS (Tarjeta completa clickeable)
function renderProductosLista(lista) {
  const contenedor = document.getElementById("productos-lista");

  if (lista.length === 0) {
    contenedor.innerHTML =
      '<p class="vacio">No hay productos en esta categoría</p>';
    return;
  }

  contenedor.innerHTML = lista
    .map(
      (p) => `
        <div class="producto-card" id="prod-${p.id}">
            <!-- Cabecera clickeable -->
            <div class="producto-header" onclick="toggleOpciones(${p.id})">
                <img src="img/${p.id}.jpg" alt="${p.name}" onerror="this.src='https://placehold.co/400x400/2c1e17/ffffff?text=Cafe'">
                <div class="producto-card-info">
                    <h4>${p.name}</h4>
                    <div class="precio">${formatPrice(p.price)}</div>
                </div>
                <div class="btn-add-icon">+</div>
            </div>
            
            <!-- Contenedor del acordeón (vacío al inicio) -->
            <div class="producto-opciones" id="opciones-${p.id}"></div>
        </div>
    `,
    )
    .join("");
}

// 4. LÓGICA DEL CARRITO (Se mantiene igual)
function agregarAlCarrito(productId, indexCarrito = null) {
  const producto = productos.find((p) => p.id === productId);
  if (!producto) return;

  // ¿Necesita opciones? (Si es Bebida y no es Agua Embotellada)
  if (producto.category === "Bebidas" && !producto.name.includes("Agua")) {
    abrirOpciones(producto, indexCarrito);
  } else {
    // Si es panadería/pastelería, se agrega directo
    guardarEnCarrito(producto, indexCarrito, []);
  }
}

// --- LÓGICA DEL ACORDEÓN DE OPCIONES ---

function toggleOpciones(productId) {
    const producto = productos.find(p => p.id === productId);
    const card = document.getElementById(`prod-${productId}`);

    if (producto.category !== 'Bebidas' || producto.name.includes('Agua')) {
        guardarEnCarrito(producto, null, [], parseFloat(producto.price));
        return;
    }

    if (card.classList.contains('open')) {
        card.classList.remove('open');
        document.body.classList.remove('acordeon-abierto'); // Mostrar FAB
        return;
    }

    document.querySelectorAll('.producto-card').forEach(c => c.classList.remove('open'));
    card.classList.add('open');
    document.body.classList.add('acordeon-abierto'); // Ocultar FAB

    const contenedorOpciones = document.getElementById(`opciones-${productId}`);
    if (contenedorOpciones.innerHTML.trim() === '') {
        seleccionesTemporales[productId] = {}; 
        
        // Generar el HTML
        contenedorOpciones.innerHTML = `
            <div class="opciones-inner">
                ${opcionesBebidas.map(grupo => {
                    // Si es de tipo contador, generamos un HTML diferente
                    if (grupo.tipo === 'contador') {
                        return `
                            <div class="option-group">
                                <h4>${grupo.titulo} <span class="req">*Requerido</span></h4>
                                <div class="contador-wrapper">
                                    <span class="opt-name">${grupo.titulo}</span>
                                    <div class="contador-controles">
                                        <button type="button" onclick="cambiarContador(${productId}, '${grupo.id}', -1, ${grupo.min}, ${grupo.max})">−</button>
                                        <span id="val-${productId}-${grupo.id}">0</span>
                                        <button type="button" onclick="cambiarContador(${productId}, '${grupo.id}', 1, ${grupo.min}, ${grupo.max})">+</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        // Si es de tipo radio normal
                        return `
                            <div class="option-group">
                                <h4>${grupo.titulo} <span class="req">*Requerido</span></h4>
                                ${grupo.opciones.map(op => `
                                    <label class="option-label" onclick="seleccionarOpcionAcordeon(${productId}, '${grupo.id}', '${op.valor}', ${op.extra}, this)">
                                        <span class="opt-name">${op.valor}</span>
                                        ${op.extra > 0 ? `<span class="opt-price">+${formatPrice(op.extra)}</span>` : ''}
                                    </label>
                                `).join('')}
                            </div>
                        `;
                    }
                }).join('')}
                <button class="btn-primary btn-agregar-acordeon" id="btn-add-${productId}" disabled onclick="confirmarAgregar(${productId})">
                    Agregar al carrito
                </button>
            </div>
        `;
    }
}

function seleccionarOpcionAcordeon(
  productId,
  grupoId,
  valor,
  extra,
  labelElement,
) {
  // Quitar la clase 'selected' de los hermanos
  const hermanos = labelElement.parentElement.querySelectorAll(".option-label");
  hermanos.forEach((h) => h.classList.remove("selected"));

  // Ponerla en el actual
  labelElement.classList.add("selected");

  // Guardar en memoria temporal
  if (!seleccionesTemporales[productId]) seleccionesTemporales[productId] = {};
  seleccionesTemporales[productId][grupoId] = { valor, extra };

  // Verificar si ya seleccionó todas las opciones requeridas
  const totalRequeridos = opcionesBebidas.length;
  const seleccionados = Object.keys(seleccionesTemporales[productId]).length;

  const btn = document.getElementById(`btn-add-${productId}`);
  if (seleccionados >= totalRequeridos) {
    btn.disabled = false;
    // Actualizar el precio del botón para mostrar el total con extras
    let precioFinal = parseFloat(
      productos.find((p) => p.id === productId).price,
    );
    for (const key in seleccionesTemporales[productId]) {
      precioFinal += seleccionesTemporales[productId][key].extra;
    }
    btn.textContent = `Agregar al carrito - ${formatPrice(precioFinal)}`;
  }
}

function cambiarContador(productId, grupoId, delta, min, max) {
    if (!seleccionesTemporales[productId]) seleccionesTemporales[productId] = {};
    
    // Obtener valor actual o inicializarlo en 0
    let valorActual = seleccionesTemporales[productId][grupoId] ? seleccionesTemporales[productId][grupoId].qty : 0;
    let nuevoValor = valorActual + delta;

    if (nuevoValor < min || nuevoValor > max) return; // Respetar límites

    // Actualizar UI
    document.getElementById(`val-${productId}-${grupoId}`).textContent = nuevoValor;

    // Guardar en memoria
    seleccionesTemporales[productId][grupoId] = { 
        qty: nuevoValor, 
        valor: `${nuevoValor} cucharada(s)`,
        extra: 0 // Si quisieras cobrar por cucharada, iría aquí
    };

    verificarBotonAcordeon(productId);
}

function verificarBotonAcordeon(productId) {
    const totalRequeridos = opcionesBebidas.length;
    const seleccionados = Object.keys(seleccionesTemporales[productId] || {}).length;

    const btn = document.getElementById(`btn-add-${productId}`);
    if (seleccionados >= totalRequeridos) {
        btn.disabled = false;
        let precioFinal = parseFloat(productos.find(p => p.id === productId).price);
        for (const key in seleccionesTemporales[productId]) {
            precioFinal += seleccionesTemporales[productId][key].extra || 0;
        }
        btn.textContent = `Agregar al carrito - ${formatPrice(precioFinal)}`;
    }
}

function confirmarAgregar(productId) {
    const producto = productos.find(p => p.id === productId);
    const selecciones = seleccionesTemporales[productId];
    
    let precioFinal = parseFloat(producto.price);
    const opcionesFinales = [];
    
    for (const key in selecciones) {
        // Solo sumar extra si existe
        if (selecciones[key].extra) precioFinal += selecciones[key].extra;
        // Guardar el texto de la opción elegida
        opcionesFinales.push(selecciones[key].valor);
    }

    guardarEnCarrito(producto, null, opcionesFinales, precioFinal);
    
    // Cerrar acordeón, mostrar FAB y limpiar
    document.getElementById(`prod-${productId}`).classList.remove('open');
    document.body.classList.remove('acordeon-abierto'); // <-- Volver a mostrar FAB
    seleccionesTemporales[productId] = {};
    document.getElementById(`opciones-${productId}`).innerHTML = '';
}

// CONFIGURACIÓN DE OPCIONES POR CATEGORÍA O PRODUCTO
// Si el producto es "Bebidas" (excepto agua), le pedirá estas opciones.
const opcionesBebidas = [
    {
        id: 'tamano',
        titulo: 'Tamaño de la bebida',
        requerido: true,
        opciones: [
            { valor: 'Chico (8oz)', extra: 0 },
            { valor: 'Mediano (12oz)', extra: 10 },
            { valor: 'Grande (16oz)', extra: 15 }
        ]
    },
    {
        id: 'cafeina',
        titulo: 'Cafeína',
        requerido: true,
        opciones: [
            { valor: 'Con cafeína', extra: 0 },
            { valor: 'Descafeinado', extra: 0 }
        ]
    },
    {
        id: 'endulzante',
        titulo: 'Tipo de Endulzante',
        requerido: true,
        opciones: [
            { valor: 'Azúcar normal', extra: 0 },
            { valor: 'Splenda', extra: 0 },
            { valor: 'Miel', extra: 5 },
            { valor: 'Mascabo', extra: 0 },
            { valor: 'Sin azúcar', extra: 0 }
        ]
    },
    {
        id: 'cucharadas',
        titulo: 'Cantidad de cucharadas', // <-- NUEVA OPCIÓN
        requerido: true,
        tipo: 'contador', // Le decimos que será un contador
        min: 0,
        max: 5,
        extraPorUnidad: 0 // Si cada cucharada costara extra, se pone aquí
    }
];

function cambiarCantidad(index, delta) {
  const item = carrito[index];
  if (!item) return;

  const nuevaCantidad = item.quantity + delta;
  if (nuevaCantidad <= 0) {
    carrito.splice(index, 1);
    renderCarrito();
    return;
  }

  if (nuevaCantidad > 10) {
    showToast(`Máximo 10 unidades`, "error");
    return;
  }
  if (nuevaCantidad > item.stock_max) {
    showToast(`Solo quedan ${item.stock_max} unidades`, "error");
    return;
  }

  item.quantity = nuevaCantidad;
  renderCarrito();
}

function configurarEventos() {
  document
    .getElementById("btn-carrito")
    .addEventListener("click", abrirCarrito);
  document
    .getElementById("cerrar-carrito")
    .addEventListener("click", cerrarCarrito);
  document.getElementById("overlay").addEventListener("click", () => {
    cerrarCarrito();
    document.getElementById("options-panel").classList.remove("open");
  });
  document
    .getElementById("btn-enviar-pedido")
    .addEventListener("click", enviarPedido);
}

function renderCarrito() {
  const contenedor = document.getElementById("carrito-items");
  const contador = document.getElementById("contador-carrito");
  const totalEl = document.getElementById("total-carrito");

  const totalItems = carrito.reduce((sum, i) => sum + i.quantity, 0);
  contador.textContent = totalItems;

  if (carrito.length === 0) {
    contenedor.innerHTML = '<p class="vacio">Aún no has agregado nada</p>';
  } else {
    contenedor.innerHTML = carrito
      .map((item, index) => {
        // Crear texto con las opciones
        const opcionesTexto =
          item.customizations && item.customizations.length > 0
            ? `<div style="font-size:0.75rem; color:#aaa; margin-top:4px;">${item.customizations.join(" • ")}</div>`
            : "";

        return `
            <div class="item-carrito" style="display:flex; justify-content:space-between; align-items:flex-start; padding: 12px 0; border-bottom: 1px solid #3a2820;">
                <div style="flex-grow:1;">
                    <strong style="display:block; font-size: 1rem;">${item.name}</strong>
                    ${opcionesTexto}
                    <div style="margin-top: 6px;">
                        <button onclick="editarItemCarrito(${index})" style="background:transparent; border:none; color:#ff9800; font-size:0.85rem; padding:0; cursor:pointer; text-decoration:underline;">✏️ Editar</button>
                    </div>
                    <small style="color: #888; display:block; margin-top:4px;">${formatPrice(item.unit_price)} c/u</small>
                </div>
                <div class="item-carrito-controles" style="display:flex; align-items:center; gap:10px;">
                    <button onclick="cambiarCantidad(${index}, -1)">−</button>
                    <span class="cantidad" style="font-weight:bold; min-width:20px; text-align:center;">${item.quantity}</span>
                    <button onclick="cambiarCantidad(${index}, 1)">+</button>
                </div>
            </div>
        `;
      })
      .join("");
  }

  const total = carrito.reduce((sum, i) => sum + i.unit_price * i.quantity, 0);
  totalEl.textContent = formatPrice(total);
}

// Función para el botón Editar
function editarItemCarrito(index) {
  const item = carrito[index];
  abrirOpciones(
    {
      id: item.product_id,
      name: item.name,
      price: item.unit_price - (item.customizations ? 10 : 0),
    },
    index,
  );
}

async function enviarPedido() {
  const nombre = document.getElementById("nombre-cliente").value.trim();
  if (!nombre) {
    showToast("Escribe tu nombre antes de enviar", "error");
    return;
  }
  if (carrito.length === 0) {
    showToast("Agrega algo al carrito primero", "error");
    return;
  }

  const btn = document.getElementById("btn-enviar-pedido");
  btn.disabled = true;
  btn.textContent = "Enviando...";

  try {
    const orden = {
      customer_name: nombre,
      items: carrito.map((item) => ({
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        customizations: item.customizations, // <-- AGREGAR ESTO
      })),
    };

    const respuesta = await api.createOrder(orden);
    showToast(`¡Pedido #${respuesta.id} enviado! ☕`, "success");

    carrito = [];
    document.getElementById("nombre-cliente").value = "";
    renderCarrito();
    cerrarCarrito();
  } catch (error) {
    showToast("Error al enviar el pedido", "error");
  } finally {
    btn.disabled = false;
    btn.textContent = "Enviar pedido";
  }
}

function abrirCarrito() {
  document.getElementById("carrito-panel").classList.add("open");
  document.getElementById("overlay").classList.add("show");
  document.body.style.overflow = "hidden"; // <-- Bloquea el scroll del fondo
}

function cerrarCarrito() {
  document.getElementById("carrito-panel").classList.remove("open");
  document.getElementById("overlay").classList.remove("show");
  document.body.style.overflow = ""; // <-- Devuelve el scroll normal
}

function configurarEventos() {
  document
    .getElementById("btn-carrito")
    .addEventListener("click", abrirCarrito);
  document
    .getElementById("cerrar-carrito")
    .addEventListener("click", cerrarCarrito);
  document.getElementById("overlay").addEventListener("click", cerrarCarrito);
  document
    .getElementById("btn-enviar-pedido")
    .addEventListener("click", enviarPedido);
}

function guardarEnCarrito(
  producto,
  indexEditar,
  opciones = [],
  precioCustom = null,
) {
  const precioUnitario = precioCustom || parseFloat(producto.price);

  if (indexEditar !== null) {
    // ESTAMOS EDITANDO UN ITEM DEL CARRITO
    carrito[indexEditar].quantity += 1; // Opcional: sumar 1, o dejarlo igual
    carrito[indexEditar].unit_price = precioUnitario;
    carrito[indexEditar].customizations = opciones;
    showToast(`Producto actualizado`, "success");
  } else {
    // ES UN PRODUCTO NUEVO
    // Verificamos si ya existe uno IGUAL (mismo producto y mismas opciones)
    const opcionesString = opciones.join(",");
    const existenteIndex = carrito.findIndex(
      (i) =>
        i.product_id === producto.id &&
        (i.customizations || []).join(",") === opcionesString,
    );

    if (existenteIndex !== -1) {
      carrito[existenteIndex].quantity++;
    } else {
      // Límite de 10
      if (carrito.length >= 30) {
        showToast("Límite de carrito alcanzado", "error");
        return;
      }

      carrito.push({
        product_id: producto.id,
        name: producto.name,
        unit_price: precioUnitario,
        quantity: 1,
        stock_max: producto.stock,
        customizations: opciones, // Guardamos el arreglo de opciones
      });
    }
    showToast(`${producto.name} agregado`, "success");
  }

  renderCarrito();
}
