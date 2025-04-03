document.addEventListener("DOMContentLoaded", async () => {
  let total_enc = document.getElementById("total_enc");
  let est_dom = document.getElementById("est_dom");
  let request_get_metrics = await fetch("../blocks/learning_style/dashboard/api/get_metrics.php");
  if (request_get_metrics.ok) {
    let response_get_metrics = await request_get_metrics.json();
    console.log(response_get_metrics)
    let total_curso = response_get_metrics["total_students_on_course"];
    let enc = response_get_metrics["total_students"]
    //total_enc.innerText = ((enc/total_curso)*100) + "%";//
    total_enc.innerText = enc + "/" + total_curso;
    let ctx_bar_ = document.getElementById("distr_bar").getContext("2d");
    let ctx_pie = document.getElementById("distr_pie").getContext("2d");
    let llave_max = "";
    let estilo_hu = "";
    let max_value = 0;
    for(let estilo in response_get_metrics){
      //console.log(estilo);
      if(response_get_metrics["data"][estilo] > max_value){
        llave_max = estilo;
        max_value = response_get_metrics[estilo];
      }
    }
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
    console.log("estilo dominante ", llave_max);
    est_dom.innerText = estilo_hu;
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
    
    let pieChart = new Chart(ctx_pie, {
      type: "pie",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Valor",
            data: data,
            backgroundColor: [
              "rgba(255, 99, 132, 0.2)",
              "rgba(54, 162, 235, 0.2)",
              "rgba(255, 206, 86, 0.2)",
              "rgba(75, 192, 192, 0.2)",
              "rgba(153, 102, 255, 0.2)",
              "rgba(255, 159, 64, 0.2)",
              "rgba(100, 221, 23, 0.2)",  // Verde brillante
              "rgba(255, 87, 34, 0.2)",    // Naranja fuerte
            ],
            borderColor: [
              "rgba(255, 99, 132, 1)",
              "rgba(54, 162, 235, 1)",
              "rgba(255, 206, 86, 1)",
              "rgba(75, 192, 192, 1)",
              "rgba(153, 102, 255, 1)",
              "rgba(255, 159, 64, 1)",
              "rgba(100, 221, 23, 1)",  // Verde brillante
              "rgba(255, 87, 34, 1)",    // Naranja fuerte
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
            text: "Distribución de estilos de aprendizaje",
          },
          legend: {
            display: false,
          },
        },
      },
    });
    let barChart = new Chart(ctx_bar_, {
      type: "bar",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Valor",
            data: data,
            backgroundColor: [
              "rgba(255, 99, 132, 0.2)",
              "rgba(54, 162, 235, 0.2)",
              "rgba(255, 206, 86, 0.2)",
              "rgba(75, 192, 192, 0.2)",
              "rgba(153, 102, 255, 0.2)",
              "rgba(255, 159, 64, 0.2)",
              "rgba(100, 221, 23, 0.2)",  // Verde brillante
              "rgba(255, 87, 34, 0.2)",    // Naranja fuerte
            ],
            borderColor: [
              "rgba(255, 99, 132, 1)",
              "rgba(54, 162, 235, 1)",
              "rgba(255, 206, 86, 1)",
              "rgba(75, 192, 192, 1)",
              "rgba(153, 102, 255, 1)",
              "rgba(255, 159, 64, 1)",
              "rgba(100, 221, 23, 1)",  // Verde brillante
              "rgba(255, 87, 34, 1)",    // Naranja fuerte
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
            text: "Distribución de estilos de aprendizaje",
          },
          legend: {
            display: false,
          },
        },
      },
    });
  }
})
