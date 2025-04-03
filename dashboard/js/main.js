document.addEventListener("DOMContentLoaded",()=>{
  let request_get_metrics = await fetch("../blocks/learning_style/dashboard/api/get_metrics.php");
  if(request_get_metrics.ok){
    let response_get_metrics = await request_get_metrics.json();
    console.log(response_get_metrics)
  }
  let ctx_bar_ = document.getElementById("distr_bar").getContext("2d");
  let ctx_pie = document.getElementById("distr_pie").getContext("2d");
  
  let pieChart = new Chart(ctx_pie, {
      type: "pie",
      data: {
        labels: ["Activo", "Reflexivo", "Verbal", "Global", "Secuencial","Intuitivo", "Sensitivo", "Visual"],
        datasets: [
          {
            label: "Distribución de estilos de aprendizaje",
            data: [30, 25,10,15,9,12,29,20],
            backgroundColor: [
              "rgba(255, 99, 132, 0.2)",
              "rgba(54, 162, 235, 0.2)",
              "rgba(255, 206, 86, 0.2)",
              "rgba(75, 192, 192, 0.2)",
              "rgba(153, 102, 255, 0.2)",
              "rgba(255, 159, 64, 0.2)",
            ],
            borderColor: [
              "rgba(255, 99, 132, 1)",
              "rgba(54, 162, 235, 1)",
              "rgba(255, 206, 86, 1)",
              "rgba(75, 192, 192, 1)",
              "rgba(153, 102, 255, 1)",
              "rgba(255, 159, 64, 1)",
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
      labels: ["Activo", "Reflexivo", "Verbal", "Global", "Secuencial","Intuitivo", "Sensitivo", "Visual"],
      datasets: [
        {
          label: "Distribución de estilos de aprendizaje",
          data: [30, 25,10,15,9,12,29,20],
          backgroundColor: [
            "rgba(255, 99, 132, 0.2)",
            "rgba(54, 162, 235, 0.2)",
            "rgba(255, 206, 86, 0.2)",
            "rgba(75, 192, 192, 0.2)",
            "rgba(153, 102, 255, 0.2)",
            "rgba(255, 159, 64, 0.2)",
          ],
          borderColor: [
            "rgba(255, 99, 132, 1)",
            "rgba(54, 162, 235, 1)",
            "rgba(255, 206, 86, 1)",
            "rgba(75, 192, 192, 1)",
            "rgba(153, 102, 255, 1)",
            "rgba(255, 159, 64, 1)",
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
})
