// js/cliente.js

let productos = [];
let carrito = [];
let productoEditandoIndex = null; // Para saber si estamos editando el carrito
let opcionesSeleccionadas = {}; // Guarda temporalmente lo que elige el usuario
let productoActual = null; // Guarda el producto que se está viendo

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
  // Tomamos los primeros 4 productos como "Populares"
  const populares = productos.slice(0, 4);
  const contenedor = document.getElementById("populares");

  contenedor.innerHTML = populares
    .map(
      (p) => `
        <div class="popular-card" onclick="agregarAlCarrito(${p.id})">
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
        <!-- EL ONCLICK ESTÁ EN TODO EL DIV -->
        <div class="producto-card" onclick="agregarAlCarrito(${p.id})">
            <img src="img/${p.id}.jpg" alt="${p.name}" onerror="this.src='https://placehold.co/400x400/2c1e17/ffffff?text=Cafe'">
            <div class="producto-card-info">
                <h4>${p.name}</h4>
                <div class="precio">${formatPrice(p.price)}</div>
            </div>
            <div class="btn-add-icon">+</div>
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

function abrirOpciones(producto, indexCarrito) {
  productoActual = producto;
  productoEditandoIndex = indexCarrito;
  opcionesSeleccionadas = {}; // Resetear

  document.getElementById("opt-product-name").textContent = producto.name;

  // Si estamos editando, cargamos lo que ya tenía seleccionado
  if (indexCarrito !== null) {
    const item = carrito[indexCarrito];
    // (Aquí en un caso real re-seleccionaríamos los radios, pero para simplificar
    // el usuario simplemente volverá a elegir).
    document.getElementById("btn-confirm-options").textContent =
      "Guardar cambios";
  } else {
    document.getElementById("btn-confirm-options").textContent =
      "Agregar al carrito";
  }

  renderOpcionesUI();
  actualizarPrecioOpciones();

  // Abrir panel y deshabilitar botón
  document.getElementById("options-panel").classList.add("open");
  document.getElementById("overlay").classList.add("show");
  document.getElementById("btn-confirm-options").disabled = true;
}

function renderOpcionesUI() {
  const contenedor = document.getElementById("options-body");
  contenedor.innerHTML = opcionesBebidas
    .map(
      (grupo) => `
        <div class="option-group" data-group="${grupo.id}">
            <h4>${grupo.titulo} <span class="req">*Requerido</span></h4>
            ${grupo.opciones
              .map(
                (op) => `
                <label class="option-label" onclick="seleccionarOpcion('${grupo.id}', '${op.valor}', ${op.extra}, this)">
                    <span class="opt-name">${op.valor}</span>
                    ${op.extra > 0 ? `<span class="opt-price">+${formatPrice(op.extra)}</span>` : ""}
                    <input type="radio" name="${grupo.id}" value="${op.valor}">
                </label>
            `,
              )
              .join("")}
        </div>
    `,
    )
    .join("");
}

function seleccionarOpcion(grupoId, valor, extra, labelElement) {
  // Quitar la clase 'selected' de los hermanos
  const hermanos = labelElement.parentElement.querySelectorAll(".option-label");
  hermanos.forEach((h) => h.classList.remove("selected"));

  // Ponerla en el actual
  labelElement.classList.add("selected");

  // Guardar en memoria
  opcionesSeleccionadas[grupoId] = { valor, extra };

  actualizarPrecioOpciones();
  verificarBotonHabilitado();
}

function actualizarPrecioOpciones() {
  let total = parseFloat(productoActual.price);
  for (const key in opcionesSeleccionadas) {
    total += opcionesSeleccionadas[key].extra;
  }
  document.getElementById("opt-total-price").textContent = formatPrice(total);
}

function verificarBotonHabilitado() {
  // Contar cuántos grupos requeridos hay
  const totalRequeridos = opcionesBebidas.length;
  // Contar cuántos se han seleccionado
  const seleccionados = Object.keys(opcionesSeleccionadas).length;

  document.getElementById("btn-confirm-options").disabled =
    seleccionados < totalRequeridos;
}

// El botón de confirmar opciones
document.getElementById("btn-confirm-options").addEventListener("click", () => {
  // Crear un arreglo con las opciones elegidas para guardarlo en el carrito
  const opcionesFinales = Object.values(opcionesSeleccionadas).map(
    (o) => o.valor,
  );

  // Calcular el precio final con los extras
  let precioFinal = parseFloat(productoActual.price);
  for (const key in opcionesSeleccionadas) {
    precioFinal += opcionesSeleccionadas[key].extra;
  }

  // Aquí pasamos los datos a la función que guarda
  guardarEnCarrito(
    productoActual,
    productoEditandoIndex,
    opcionesFinales,
    precioFinal,
  );

  // Cerrar panel
  document.getElementById("options-panel").classList.remove("open");
  document.getElementById("overlay").classList.remove("show");
});

// CONFIGURACIÓN DE OPCIONES POR CATEGORÍA O PRODUCTO
// Si el producto es "Bebidas" (excepto agua), le pedirá estas opciones.
const opcionesBebidas = [
  {
    id: "tamano",
    titulo: "Tamaño de la bebida",
    requerido: true,
    opciones: [
      { valor: "Chico (8oz)", extra: 0 },
      { valor: "Mediano (12oz)", extra: 10 }, // Suma $10 al precio
      { valor: "Grande (16oz)", extra: 15 },
    ],
  },
  {
    id: "cafeina",
    titulo: "Cafeína",
    requerido: true,
    opciones: [
      { valor: "Con cafeína", extra: 0 },
      { valor: "Descafeinado", extra: 0 },
    ],
  },
  {
    id: "azucar",
    titulo: "Endulzante",
    requerido: true,
    opciones: [
      { valor: "Azúcar normal", extra: 0 },
      { valor: "Splenda", extra: 0 },
      { valor: "Miel", extra: 5 },
      { valor: "Mascabo", extra: 0 },
      { valor: "Sin azúcar", extra: 0 },
    ],
  },
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
  document.getElementById("cerrar-options").addEventListener("click", () => {
    document.getElementById("options-panel").classList.remove("open");
    document.getElementById("overlay").classList.remove("show");
  });
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
}

function cerrarCarrito() {
  document.getElementById("carrito-panel").classList.remove("open");
  document.getElementById("overlay").classList.remove("show");
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
