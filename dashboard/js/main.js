let exp = false;
document.addEventListener("DOMContentLoaded", async () => {
  let total_enc = document.getElementById("total_enc");
  let est_dom = document.getElementById("est_dom");
  let est_men_dom = document.getElementById("est_men_dom");
  let container_blocks_exp = document.getElementById("learning_style_exp");
  let actor_expandir = document.getElementById("expandir_actor");
  let icon_exp = document.getElementById("icon_exp");

  const url_params = new URLSearchParams(window.location.search);
  const course_id = url_params.get("id"); // Esto obtiene el valor del parámetro "id" en la URL
  let form = new FormData();
  form.append("id", course_id);
  let request_get_metrics = await fetch(
    "../blocks/learning_style/dashboard/api/get_metrics.php",
    {
      method: "POST",
      body: form,
    }
  );
  if (request_get_metrics.ok) {
    let response_get_metrics = await request_get_metrics.json();
    console.log(response_get_metrics);
    let total_curso = response_get_metrics["total_students_on_course"];
    let enc = response_get_metrics["total_students"];
    total_enc.innerText = Math.floor((enc / total_curso) * 100) + "%";

    let llave_max = "";
    let llave_min = "";
    let estilo_hu = "";
    let estilo_men = "";
    let max_value = 0;
    let min_value = 0;

    //Calculo estilo dominante y menos dominante
    for (let estilo in response_get_metrics["data"]) {
      if (response_get_metrics["data"][estilo] > max_value) {
        llave_max = estilo;
        max_value = response_get_metrics["data"][estilo];
      }
    }
    min_value = max_value;
    for (let estilo in response_get_metrics["data"]) {
      if (response_get_metrics["data"][estilo] < min_value) {
        llave_min = estilo;
        min_value = response_get_metrics["data"][estilo];
      }
    }
    //Determina estilo dominante
    switch (llave_max) {
      case "num_act":
        estilo_hu = "Activo";
        break;
      case "num_ref":
        estilo_hu = "Reflexivo";
        break;
      case "num_vis":
        estilo_hu = "Visual";
        break;
      case "num_vrb":
        estilo_hu = "Verbal";
        break;
      case "num_sen":
        estilo_hu = "Sensorial";
        break;
      case "num_int":
        estilo_hu = "Intuitivo";
        break;
      case "num_sec":
        estilo_hu = "Secuencial";
        break;
      case "num_glo":
        estilo_hu = "Global";
        break;
      default:
        estilo_hu = "N/A";
        break;
    }

    //Determina estilo menos dominante
    switch (llave_min) {
      case "num_act":
        estilo_men = "Activo";
        break;
      case "num_ref":
        estilo_men = "Reflexivo";
        break;
      case "num_vis":
        estilo_men = "Visual";
        break;
      case "num_vrb":
        estilo_men = "Verbal";
        break;
      case "num_sen":
        estilo_men = "Sensorial";
        break;
      case "num_int":
        estilo_men = "Intuitivo";
        break;
      case "num_sec":
        estilo_men = "Secuencial";
        break;
      case "num_glo":
        estilo_men = "Global";
        break;
      default:
        estilo_men = "N/A";
        break;
    }

    //Muestra resulstados
    console.log("estilo dominante ", llave_max);
    est_dom.innerText = estilo_hu;
    console.log("Estilo menos dominante: ", llave_min);
    est_men_dom.innerText = estilo_men;

    //Grafico
    let labels = [];
    let data = [];
    let descriptions = [];
    data.push(response_get_metrics["data"]["num_act"]);
    labels.push("Activo");
    descriptions.push(`
        <li>Propiciar actividades prácticas.</li>
        <li>Fomentar resolución de problemas y proyectos.</li>
        <li>Incentivar discusiones y trabajo en grupo.</li>
    `);
    data.push(response_get_metrics["data"]["num_ref"]);
    labels.push("Reflexivo");
    descriptions.push(`
        <li>Asignar lecturas reflexivas.</li>
        <li>Promover la toma de notas y la reflexión.</li>
        <li>Utilizar análisis de casos y autoevaluaciones.</li>
    `);
    data.push(response_get_metrics["data"]["num_sen"]);
    labels.push("Sensitivo");
    descriptions.push(`
        <li>Diseñar actividades de observación y aplicación práctica.</li>
        <li>Usar ejemplos concretos y proyectos de laboratorio.</li>
    `);
    data.push(response_get_metrics["data"]["num_int"]);
    labels.push("Intuitivo");
    descriptions.push(`
        <li>Proponer búsqueda de patrones y conexiones.</li>
        <li>Emplear analogías e historias.</li>
        <li>Fomentar actividades creativas y resolución de problemas complejos.</li>
    `);
    data.push(response_get_metrics["data"]["num_vis"]);
    labels.push("Visual");
    descriptions.push(`
        <li>Incorporar gráficos, diagramas, videos y mapas mentales.</li>
        <li>Fomentar el uso de organizadores gráficos, como líneas de tiempo, cuadros comparativos y esquemas jerárquicos.</li>
    `);
    data.push(response_get_metrics["data"]["num_vrb"]);
    labels.push("Verbal");
    descriptions.push(`
        <li>Promover lectura, escritura y discusión en grupos.</li>
        <li>Fomentar técnicas de memorización verbal.</li>
    `);
    data.push(response_get_metrics["data"]["num_sec"]);
    labels.push("Secuencial");
    descriptions.push(`
        <li>Organizar contenidos de manera lógica.</li>
        <li>Proponer actividades paso a paso.</li>
    `);
    data.push(response_get_metrics["data"]["num_glo"]);
    labels.push("Global");
    descriptions.push(`
        <li>Presentar una visión general antes de los detalles.</li>
        <li>Fomentar conexiones y proyectos integradores.</li>
    `);
    
    let chartTypeSelector = document.getElementById("chart-type-selector");
    let savedChartType = localStorage.getItem("chartType");
    let chart; // Variable global del gráfico

    const context = document.getElementById("grafic").getContext("2d"); // Contexto del canvas
    // Verificar si existe un tipo de gráfico guardado en localStorage
    if (savedChartType) {
      chartTypeSelector.value = savedChartType;
      console.log("Se recuperó el tipo de gráfico");
      cambiarGrafico(savedChartType); // Cambiar el gráfico según lo guardado en localStorage
    } else {
      console.log("No hay gráfico por defecto, se crea uno.");
      localStorage.setItem("chartType", "pie"); // Establecer 'pie' por defecto si no hay valor guardado
      cambiarGrafico("pie"); // Crear gráfico por defecto
    }

    // Función para cambiar el gráfico
    chartTypeSelector.addEventListener("change", function () {
      let selectedType = chartTypeSelector.value;
      localStorage.setItem("chartType", selectedType); // Guardar el tipo de gráfico seleccionado
      cambiarGrafico(selectedType); // Cambiar el gráfico según el tipo seleccionado
    });

    // Función para crear y destruir gráficos
    function cambiarGrafico(tipo) {
      if (chart) {
        chart.destroy(); // Destruir el gráfico existente
      }

      // Crear el nuevo gráfico con el tipo seleccionado
      chart = crearGrafico(
        tipo,
        context,
        labels,
        data,
        "Distribución de estilos de aprendizaje"
      );
    }
    
    ordenar_e_insertar(labels, data, descriptions, container_blocks_exp);
    actor_expandir.addEventListener("click", () => {
      if (exp) {
        //se cierra el expandible
        exp = false;
        container_blocks_exp.className = "learning_style_exp_close";
        icon_exp.style.transform = "rotate(0deg)";
      } else {
        //se abre el expandible
        exp = true;
        container_blocks_exp.className = "learning_style_exp_open";
        icon_exp.style.transform = "rotate(180deg)";
      }
    });
  }
});
function crearGrafico(tipo, ctx, etiquetas, valores, titulo) {
  return new Chart(ctx, {
    type: tipo,
    data: {
      labels: etiquetas,
      datasets: [
        {
          label: "Valor",
          data: valores,
          backgroundColor: [
            "rgba(255, 99, 132, 0.2)",
            "rgba(54, 162, 235, 0.2)",
            "rgba(255, 206, 86, 0.2)",
            "rgba(75, 192, 192, 0.2)",
            "rgba(153, 102, 255, 0.2)",
            "rgba(255, 159, 64, 0.2)",
            "rgba(100, 221, 23, 0.2)",
            "rgba(255, 87, 34, 0.2)",
          ],
          borderColor: [
            "rgba(255, 99, 132, 1)",
            "rgba(54, 162, 235, 1)",
            "rgba(255, 206, 86, 1)",
            "rgba(75, 192, 192, 1)",
            "rgba(153, 102, 255, 1)",
            "rgba(255, 159, 64, 1)",
            "rgba(100, 221, 23, 1)",
            "rgba(255, 87, 34, 1)",
          ],
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: titulo,
        },
        legend: {
          display: false,
        },
      },
    },
  });
}
function ordenar_e_insertar(
  array_cuali,
  array_cuant,
  array_description,
  container
) {
  // Crear un array de pares de valores [número, nombre]
  let combinados = array_cuant.map((num, index) => [
    num,
    array_cuali[index],
    array_description[index],
  ]);

  // Ordenar el array combinado basado en el valor numérico (primer valor de cada sub-array)
  combinados.sort((a, b) => b[0] - a[0]);

  // Descomponer el array combinado de nuevo en dos arrays ordenados
  const desc = [[], [], []];

  combinados.forEach(([a, b, c]) => {
    desc[0].push(a);
    desc[1].push(b);
    desc[2].push(c);
  });

  let colors = ["#159600","#007aa7","#6F42C1","#DC3545","#FD7E14","#FFC107","#a76628","#000000"];
  for (let i = 0; i < desc[0].length; i++) {
    //console.log(i, nombres.length);
    let block_html = document.createElement("div");
    if(desc[0][i]>0){
      block_html.innerHTML = `<div class="flex block_reco_style" style="border-color: ${colors[i]}"><span style="color: ${colors[i]}" >${desc[1][i]}</span><span style="color: ${colors[i]}">${desc[0][i]}</span></div>
                            Se le recomienda al docente:
                            <div>
                                <ul>${desc[2][i]}</ul>
                            </div>
    `;
    }
    console.log(block_html);
    container.appendChild(block_html);
  }
}
