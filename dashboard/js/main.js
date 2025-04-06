document.addEventListener("DOMContentLoaded", async () => {
  let total_enc = document.getElementById("total_enc");
  let est_dom = document.getElementById("est_dom");
  let est_men_dom = document.getElementById("est_men_dom");
  let request_get_metrics = await fetch("../blocks/learning_style/dashboard/api/get_metrics.php");
  if (request_get_metrics.ok) {
    let response_get_metrics = await request_get_metrics.json();
    console.log(response_get_metrics)
    let total_curso = response_get_metrics["total_students_on_course"];
    let enc = response_get_metrics["total_students"];
    total_enc.innerText = enc + "/" + total_curso;
    let ctx_bar_ = document.getElementById("distr_bar").getContext("2d");
    let ctx_pie = document.getElementById("distr_pie").getContext("2d");
    
    let llave_max = "";
    let llave_min = "";
    let estilo_hu = "";
    let estilo_men = "";
    let max_value = 0;
    let min_value = 0;

    //Calculo estilo dominante y menos dominante 
    for(let estilo in response_get_metrics){
      if(response_get_metrics["data"][estilo] > max_value){
        llave_max = estilo;
        max_value = response_get_metrics[estilo];
      }
      if (response_get_metrics["data"][estilo] < min_value) {
        llave_min = estilo;
        min_value = response_get_metrics["data"][estilo];
      }
    }

    //Determina estilo dominante
    switch(llave_max){
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
    data.push(response_get_metrics["data"]["num_act"]);
    labels.push("Activo");
    data.push(response_get_metrics["data"]["num_ref"]);
    labels.push("Reflexivo");
    data.push(response_get_metrics["data"]["num_sen"]);
    labels.push("Sensitivo");
    data.push(response_get_metrics["data"]["num_int"]);
    labels.push("Intuitivo");
    data.push(response_get_metrics["data"]["num_vis"]);
    labels.push("Visual");
    data.push(response_get_metrics["data"]["num_vrb"]);
    labels.push("Verbal");
    data.push(response_get_metrics["data"]["num_sec"]);
    labels.push("Secuencial");
    data.push(response_get_metrics["data"]["num_glo"]);
    labels.push("Global");
    crearGrafico("pie", ctx_pie, labels, data, "Distribución de estilos de aprendizaje");
    crearGrafico("bar", ctx_bar_, labels, data, "Distribución de estilos de aprendizaje");
  }
})
function crearGrafico(tipo, ctx, etiquetas, valores, titulo) {
  return new Chart(ctx, {
    type: tipo,
    data: {
      labels: etiquetas,
      datasets: [{
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
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: true,
          text: titulo
        },
        legend: {
          display: false
        }
      }
    }
  });
}
