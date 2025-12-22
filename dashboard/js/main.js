require(['core/chartjs', 'core/str', 'core/config'], function(Chart, Str, Config) {
  let exp = false;

  const loadStrings = async () => {
    const reqs = [
      {key: 'visual', component: 'block_learning_style'},
      {key: 'sensorial', component: 'block_learning_style'},
      {key: 'active', component: 'block_learning_style'},
      {key: 'global', component: 'block_learning_style'},
      {key: 'verbal', component: 'block_learning_style'},
      {key: 'intuitive', component: 'block_learning_style'},
      {key: 'reflexive', component: 'block_learning_style'},
      {key: 'sequential', component: 'block_learning_style'},

      {key: 'visual_rec1', component: 'block_learning_style'},
      {key: 'visual_rec2', component: 'block_learning_style'},
      {key: 'sensory_rec1', component: 'block_learning_style'},
      {key: 'sensory_rec2', component: 'block_learning_style'},
      {key: 'active_rec1', component: 'block_learning_style'},
      {key: 'active_rec2', component: 'block_learning_style'},
      {key: 'active_rec3', component: 'block_learning_style'},
      {key: 'global_rec1', component: 'block_learning_style'},
      {key: 'global_rec2', component: 'block_learning_style'},
      {key: 'verbal_rec1', component: 'block_learning_style'},
      {key: 'verbal_rec2', component: 'block_learning_style'},
      {key: 'intuitive_rec1', component: 'block_learning_style'},
      {key: 'intuitive_rec2', component: 'block_learning_style'},
      {key: 'intuitive_rec3', component: 'block_learning_style'},
      {key: 'reflexive_rec1', component: 'block_learning_style'},
      {key: 'reflexive_rec2', component: 'block_learning_style'},
      {key: 'reflexive_rec3', component: 'block_learning_style'},
      {key: 'sequential_rec1', component: 'block_learning_style'},
      {key: 'sequential_rec2', component: 'block_learning_style'},

      {key: 'teacher_recommendation', component: 'block_learning_style'},
      {key: 'dashboard_of', component: 'block_learning_style'},
      {key: 'dashboard_no_dominance', component: 'block_learning_style'},
      {key: 'chart_title', component: 'block_learning_style'},
      {key: 'no_completed_tests_title', component: 'block_learning_style'},
      {key: 'no_completed_tests_message', component: 'block_learning_style'},
      {key: 'na_label', component: 'block_learning_style'},
    ];

    const values = await Str.get_strings(reqs);
    let i = 0;

    return {
      label_visual: values[i++],
      label_sensory: values[i++],
      label_active: values[i++],
      label_global: values[i++],
      label_verbal: values[i++],
      label_intuitive: values[i++],
      label_reflexive: values[i++],
      label_sequential: values[i++],

      visual_rec1: values[i++],
      visual_rec2: values[i++],
      sensory_rec1: values[i++],
      sensory_rec2: values[i++],
      active_rec1: values[i++],
      active_rec2: values[i++],
      active_rec3: values[i++],
      global_rec1: values[i++],
      global_rec2: values[i++],
      verbal_rec1: values[i++],
      verbal_rec2: values[i++],
      intuitive_rec1: values[i++],
      intuitive_rec2: values[i++],
      intuitive_rec3: values[i++],
      reflexive_rec1: values[i++],
      reflexive_rec2: values[i++],
      reflexive_rec3: values[i++],
      sequential_rec1: values[i++],
      sequential_rec2: values[i++],

      teacher_recommendation: values[i++],
      dashboard_of: values[i++],
      dashboard_no_dominance: values[i++],
      chart_title: values[i++],
      no_completed_tests_title: values[i++],
      no_completed_tests_message: values[i++],
      na_label: values[i++],
    };
  };
  
  const initDashboard = async () => {
  let total_enc = document.getElementById("total_enc");
  let est_dom = document.getElementById("est_dom");
  let est_men_dom = document.getElementById("est_men_dom");
  let container_blocks_exp = document.getElementById("learning_style_exp");
  let actor_expandir = document.getElementById("expandir_actor");
  let icon_exp = document.getElementById("icon_exp");

  let strings = {};
  try {
    strings = await loadStrings();
  } catch (e) {
    console.error(e);
    strings = {};
  }

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
  
  try {
    // Fetch metrics from the server-side metrics endpoint.
    let response_get_metrics = null;
    try {
      const endpoint = Config.wwwroot + '/blocks/learning_style/dashboard/metrics.php?courseid=' + encodeURIComponent(course_id) + '&sesskey=' + encodeURIComponent(Config.sesskey);
      const resp = await fetch(endpoint, { credentials: 'same-origin' });
      if (!resp.ok) {
        throw new Error('Network response was not ok: ' + resp.status);
      }
      response_get_metrics = await resp.json();
      if (response_get_metrics && response_get_metrics.error) {
        throw new Error(response_get_metrics.error);
      }
    } catch (e) {
      console.error('Could not fetch metrics:', e);
      total_enc.innerText = strings.na_label || 'N/A';
      est_dom.innerText = strings.na_label || 'N/A';
      est_men_dom.innerText = strings.na_label || 'N/A';
      return;
    }
    let total_curso = response_get_metrics["total_students_on_course"] || 0;
    let enc = response_get_metrics["total_students"] || 0;
    // Evitar división por cero: si no hay usuarios matriculados, mostrar 0% de forma segura.
    const ofWord = strings.dashboard_of || 'de';
    if (!total_curso) {
      total_enc.innerText = "0% (" + enc + " " + ofWord + " " + total_curso + ")";
    } else {
      total_enc.innerText = Math.floor((enc / total_curso) * 100) + "% (" + enc + " " + ofWord + " " + total_curso + ")";
    }

    //Grafico
    
    let labels = [];
    let data = [];
    let descriptions = [];
    
    data.push(response_get_metrics["data"]["num_vis"]);
    labels.push(strings.label_visual || "Visual");
    descriptions.push([strings.visual_rec1, strings.visual_rec2]);
    data.push(response_get_metrics["data"]["num_sen"]);
    labels.push(strings.label_sensory || "Sensitivo");
    descriptions.push([strings.sensory_rec1, strings.sensory_rec2]);
    data.push(response_get_metrics["data"]["num_act"]);
    labels.push(strings.label_active || "Activo");
    descriptions.push([strings.active_rec1, strings.active_rec2, strings.active_rec3]);
    data.push(response_get_metrics["data"]["num_glo"]);
    labels.push(strings.label_global || "Global");
    descriptions.push([strings.global_rec1, strings.global_rec2]);
    data.push(response_get_metrics["data"]["num_vrb"]);
    labels.push(strings.label_verbal || "Verbal");
    descriptions.push([strings.verbal_rec1, strings.verbal_rec2]);
    data.push(response_get_metrics["data"]["num_int"]);
    labels.push(strings.label_intuitive || "Intuitivo");
    descriptions.push([strings.intuitive_rec1, strings.intuitive_rec2, strings.intuitive_rec3]);
    data.push(response_get_metrics["data"]["num_ref"]);
    labels.push(strings.label_reflexive || "Reflexivo");
    descriptions.push([strings.reflexive_rec1, strings.reflexive_rec2, strings.reflexive_rec3]);
    data.push(response_get_metrics["data"]["num_sec"]);
    labels.push(strings.label_sequential || "Secuencial");
    descriptions.push([strings.sequential_rec1, strings.sequential_rec2]);
    
    
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
        messageContainer.textContent = '';
        const icon = document.createElement('i');
        icon.className = 'fa fa-chart-bar';
        icon.style.fontSize = '48px';
        icon.style.color = '#6c757d';
        icon.style.marginBottom = '15px';
        icon.style.display = 'block';

        const titleEl = document.createElement('h5');
        titleEl.style.color = '#495057';
        titleEl.style.marginBottom = '10px';
        titleEl.textContent = title;

        const messageEl = document.createElement('p');
        messageEl.style.color = '#6c757d';
        messageEl.style.margin = '0';
        messageEl.textContent = message;

        messageContainer.appendChild(icon);
        messageContainer.appendChild(titleEl);
        messageContainer.appendChild(messageEl);
      }
      
      return;
    }

    // Show dominant and least dominant style blocks (only when there is data)
    const dominantBlock = document.getElementById('dominant-style-block');
    const leastDominantBlock = document.getElementById('least-dominant-style-block');
    if (dominantBlock) {
      dominantBlock.style.display = 'block';
    }
    if (leastDominantBlock) {
      leastDominantBlock.style.display = 'block';
    }

    // Dominance is computed server-side (supports ties) to avoid duplicating logic.
    const keyToLabel = {
      num_act: strings.label_active || 'Activo',
      num_ref: strings.label_reflexive || 'Reflexivo',
      num_vis: strings.label_visual || 'Visual',
      num_vrb: strings.label_verbal || 'Verbal',
      num_sen: strings.label_sensory || 'Sensorial',
      num_int: strings.label_intuitive || 'Intuitivo',
      num_sec: strings.label_sequential || 'Secuencial',
      num_glo: strings.label_global || 'Global',
    };

    const normalizeKeys = (keys) => {
      if (!Array.isArray(keys)) {
        return [];
      }
      // unique + stable order
      return Array.from(new Set(keys.filter(Boolean)));
    };

    const labelsForKeys = (keys) => {
      const labels = normalizeKeys(keys)
        .map((k) => keyToLabel[k])
        .filter(Boolean);
      if (!labels.length) {
        return strings.na_label || 'N/A';
      }
      return labels.join(' / ');
    };

    const setEqual = (a, b) => {
      const aa = normalizeKeys(a);
      const bb = normalizeKeys(b);
      if (aa.length !== bb.length) {
        return false;
      }
      const s = new Set(aa);
      return bb.every((x) => s.has(x));
    };

    const dominantKeys = response_get_metrics.dominant_keys;
    const leastKeys = response_get_metrics.least_dominant_keys;

    const dominanceIsFlat = !!response_get_metrics.dominance_is_flat;
    const sameLists = dominanceIsFlat || setEqual(dominantKeys, leastKeys);

    if (sameLists) {
      est_dom.innerText = strings.dashboard_no_dominance || 'Sin dominancia clara';
      est_men_dom.innerText = strings.na_label || 'N/A';
      if (leastDominantBlock) {
        leastDominantBlock.style.display = 'none';
      }
    } else {
      est_dom.innerText = labelsForKeys(dominantKeys);
      est_men_dom.innerText = labelsForKeys(leastKeys);
      if (leastDominantBlock) {
        leastDominantBlock.style.display = 'block';
      }
    }
    
    const context = document.getElementById("grafic").getContext("2d"); // Contexto del canvas
    // Verificar si existe un tipo de gráfico guardado en localStorage
    if (savedChartType) {
      chartTypeSelector.value = savedChartType;
      cambiarGrafico(savedChartType); // Cambiar el gráfico según lo guardado en localStorage
    } else {
      localStorage.setItem("chartType", "pie"); // Establecer 'pie' por defecto si no hay valor guardado
      chartTypeSelector.value = "pie";
      cambiarGrafico("pie"); // Crear gráfico por defecto
    }

    // Función para cambiar el gráfico. Use `onchange` to ensure a single handler
    // even if `initDashboard` runs multiple times.
    if (chartTypeSelector) {
      chartTypeSelector.onchange = function () {
        const selectedType = chartTypeSelector.value;
        try {
          localStorage.setItem("chartType", selectedType);
        } catch (e) {
          // ignore storage errors (e.g., quota or disabled)
        }
        cambiarGrafico(selectedType);
      };
    }

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
    // Ensure a single click handler for the expand control to avoid duplicates.
    if (actor_expandir) {
      actor_expandir.onclick = () => {
        if (exp) {
          // se cierra el expandible
          exp = false;
          if (container_blocks_exp) container_blocks_exp.className = "learning_style_exp_close";
          if (icon_exp) icon_exp.style.transform = "rotate(0deg)";
        } else {
          // se abre el expandible
          exp = true;
          if (container_blocks_exp) container_blocks_exp.className = "learning_style_exp_open";
          if (icon_exp) icon_exp.style.transform = "rotate(180deg)";
        }
      };
    }
  } catch (e) {
    console.error(e);
    total_enc.innerText = "Error";
    est_dom.innerText = strings.na_label || "N/A";
    est_men_dom.innerText = strings.na_label || "N/A";
    return;
  }
  };

  if (document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", initDashboard);
  } else {
    initDashboard();
  }

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
      const block = document.createElement('div');

      const header = document.createElement('div');
      header.className = 'flex block_reco_style';
      header.style.borderColor = colors[i];

      const nameSpan = document.createElement('span');
      nameSpan.style.color = colors[i];
      nameSpan.textContent = desc[1][i];

      const valueSpan = document.createElement('span');
      valueSpan.style.color = colors[i];
      valueSpan.textContent = String(desc[0][i]);

      header.appendChild(nameSpan);
      header.appendChild(valueSpan);

      const recoText = document.createElement('div');
      recoText.textContent = recommendationText;

      const recoWrap = document.createElement('div');
      const ul = document.createElement('ul');
      const items = Array.isArray(desc[2][i]) ? desc[2][i] : [];
      items.filter(Boolean).forEach((text) => {
        const li = document.createElement('li');
        li.textContent = text;
        ul.appendChild(li);
      });
      recoWrap.appendChild(ul);

      block.appendChild(header);
      block.appendChild(recoText);
      block.appendChild(recoWrap);

      container.appendChild(block);
    }
  }
  }
});
