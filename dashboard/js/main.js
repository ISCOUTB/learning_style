let exp = false;
document.addEventListener("DOMContentLoaded", async () => {
  let total_enc = document.getElementById("total_enc");
  let est_dom = document.getElementById("est_dom");
  let est_men_dom = document.getElementById("est_men_dom");
  let container_blocks_exp = document.getElementById("learning_style_exp");
  let actor_expandir = document.getElementById("expandir_actor");
  let icon_exp = document.getElementById("icon_exp");

  // Get strings from injected translations
  const strings = window.learningStyleStrings || {};

  // Obtener el course_id desde el atributo data-courseid del contenedor
  const dashboardContainer = document.getElementById("learning-style-dashboard");
  let course_id = dashboardContainer ? dashboardContainer.getAttribute("data-courseid") : null;
  
  // Si no está en el contenedor, intentar obtenerlo de la URL (para compatibilidad con admin_view.php)
  if (!course_id) {
    const url_params = new URLSearchParams(window.location.search);
    course_id = url_params.get("id") || url_params.get("courseid");
  }
  
  if (!course_id) {
    console.error("No se pudo obtener el ID del curso");
    total_enc.innerText = "Error";
    est_dom.innerText = "N/A";
    est_men_dom.innerText = "N/A";
    return;
  }
  
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
    let total_curso = response_get_metrics["total_students_on_course"];
    let enc = response_get_metrics["total_students"];
    total_enc.innerText = Math.floor((enc / total_curso) * 100) + "% (" + enc + " de " + total_curso + ")";

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
        estilo_hu = strings.label_active || "Activo";
        break;
      case "num_ref":
        estilo_hu = strings.label_reflexive || "Reflexivo";
        break;
      case "num_vis":
        estilo_hu = strings.label_visual || "Visual";
        break;
      case "num_vrb":
        estilo_hu = strings.label_verbal || "Verbal";
        break;
      case "num_sen":
        estilo_hu = strings.label_sensory || "Sensorial";
        break;
      case "num_int":
        estilo_hu = strings.label_intuitive || "Intuitivo";
        break;
      case "num_sec":
        estilo_hu = strings.label_sequential || "Secuencial";
        break;
      case "num_glo":
        estilo_hu = strings.label_global || "Global";
        break;
      default:
        estilo_hu = strings.na_label || "N/A";
        break;
    }

    //Determina estilo menos dominante
    switch (llave_min) {
      case "num_act":
        estilo_men = strings.label_active || "Activo";
        break;
      case "num_ref":
        estilo_men = strings.label_reflexive || "Reflexivo";
        break;
      case "num_vis":
        estilo_men = strings.label_visual || "Visual";
        break;
      case "num_vrb":
        estilo_men = strings.label_verbal || "Verbal";
        break;
      case "num_sen":
        estilo_men = strings.label_sensory || "Sensorial";
        break;
      case "num_int":
        estilo_men = strings.label_intuitive || "Intuitivo";
        break;
      case "num_sec":
        estilo_men = strings.label_sequential || "Secuencial";
        break;
      case "num_glo":
        estilo_men = strings.label_global || "Global";
        break;
      default:
        estilo_men = strings.na_label || "N/A";
        break;
    }

    //Muestra resultados
    est_dom.innerText = estilo_hu;
    est_men_dom.innerText = estilo_men;

    //Grafico
    
    let labels = [];
    let data = [];
    let descriptions = [];
    
    data.push(response_get_metrics["data"]["num_vis"]);
    labels.push(strings.label_visual || "Visual");
    descriptions.push(`
        <li>${strings.visual_rec1}</li>
        <li>${strings.visual_rec2}</li>
    `);
    data.push(response_get_metrics["data"]["num_sen"]);
    labels.push(strings.label_sensory || "Sensitivo");
    descriptions.push(`
        <li>${strings.sensory_rec1}</li>
        <li>${strings.sensory_rec2}</li>
    `);
    data.push(response_get_metrics["data"]["num_act"]);
    labels.push(strings.label_active || "Activo");
    descriptions.push(`
        <li>${strings.active_rec1}</li>
        <li>${strings.active_rec2}</li>
        <li>${strings.active_rec3}</li>
    `);
    data.push(response_get_metrics["data"]["num_glo"]);
    labels.push(strings.label_global || "Global");
    descriptions.push(`
        <li>${strings.global_rec1}</li>
        <li>${strings.global_rec2}</li>
    `);
    data.push(response_get_metrics["data"]["num_vrb"]);
    labels.push(strings.label_verbal || "Verbal");
    descriptions.push(`
        <li>${strings.verbal_rec1}</li>
        <li>${strings.verbal_rec2}</li>
    `);
    data.push(response_get_metrics["data"]["num_int"]);
    labels.push(strings.label_intuitive || "Intuitivo");
    descriptions.push(`
        <li>${strings.intuitive_rec1}</li>
        <li>${strings.intuitive_rec2}</li>
        <li>${strings.intuitive_rec3}</li>
    `);
    data.push(response_get_metrics["data"]["num_ref"]);
    labels.push(strings.label_reflexive || "Reflexivo");
    descriptions.push(`
        <li>${strings.reflexive_rec1}</li>
        <li>${strings.reflexive_rec2}</li>
        <li>${strings.reflexive_rec3}</li>
    `);
    data.push(response_get_metrics["data"]["num_sec"]);
    labels.push(strings.label_sequential || "Secuencial");
    descriptions.push(`
        <li>${strings.sequential_rec1}</li>
        <li>${strings.sequential_rec2}</li>
    `);
    
    
    let chartTypeSelector = document.getElementById("chart-type-selector");
    let savedChartType = localStorage.getItem("chartType");
    let chart; // Variable global del gráfico

    // Check if there are any completed tests
    const hasData = enc > 0 && data.some(value => value > 0);
    
    if (!hasData) {
      // Hide charts section
      const chartsSection = document.getElementById('charts-section');
      if (chartsSection) {
        chartsSection.style.display = 'none';
      }
      
      // Show message in designated container
      const messageContainer = document.getElementById('no-data-message');
      if (messageContainer) {
        const title = strings.no_completed_tests_title || 'No hay tests completados';
        const message = strings.no_completed_tests_message || 'Los gráficos y estadísticas se mostrarán cuando los estudiantes completen el test de estilos de aprendizaje.';
        messageContainer.style.display = 'block';
        messageContainer.style.cssText = 'text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6; margin: 20px 0; display: block;';
        messageContainer.innerHTML = '<i class="fa fa-chart-bar" style="font-size: 48px; color: #6c757d; margin-bottom: 15px; display: block;"></i><h5 style="color: #495057; margin-bottom: 10px;">' + title + '</h5><p style="color: #6c757d; margin: 0;">' + message + '</p>';
      }
      return;
    }
    
    const context = document.getElementById("grafic").getContext("2d"); // Contexto del canvas
    // Verificar si existe un tipo de gráfico guardado en localStorage
    if (savedChartType) {
      chartTypeSelector.value = savedChartType;
      cambiarGrafico(savedChartType); // Cambiar el gráfico según lo guardado en localStorage
    } else {
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
        strings.chart_title || "Distribución de estilos de aprendizaje"
      );
    }
    
    ordenar_e_insertar(labels, data, descriptions, container_blocks_exp, strings);
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
  container,
  strings
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

  // Get recommendation text from strings
  const recommendationText = strings.teacher_recommendation || 'Se le recomienda al docente:';
  
  let colors = ["#159600","#007aa7","#6F42C1","#DC3545","#FD7E14","#FFC107","#a76628","#000000"];
  for (let i = 0; i < desc[0].length; i++) {
    if(desc[0][i] > 0){
      let block_html = document.createElement("div");
      block_html.innerHTML = `<div class="flex block_reco_style" style="border-color: ${colors[i]}"><span style="color: ${colors[i]}" >${desc[1][i]}</span><span style="color: ${colors[i]}">${desc[0][i]}</span></div>
                            ${recommendationText}
                            <div>
                                <ul>${desc[2][i]}</ul>
                            </div>
    `;
      container.appendChild(block_html);
    }
  }
}
