<link rel="stylesheet" href="../blocks/learning_style/dashboard/css/style.css">

<style>
/* Estilos para los tooltips */
[data-tooltip] {
    position: relative;
    cursor: pointer;
}

[data-tooltip]::after {
    content: attr(data-tooltip);
    visibility: hidden;
    opacity: 0;
    position: absolute;
    bottom: 100%;
    left: 0;
    width: 100%;
    background-color: #333;
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 13px;
    z-index: 10;
    white-space: normal;
    word-wrap: break-word;
    box-sizing: border-box;
    transition: opacity 0.3s ease;
    pointer-events: none;
    margin-bottom: 8px;
}

[data-tooltip]:hover::after {
    visibility: visible;
    opacity: 1;
}
</style>

<div class="block_term" data-tooltip="Porcentaje de estudiantes encuestados en el curso.">
    <div class="flex">
        <div class="icon_term">
            <img src="../blocks/learning_style/dashboard/assets/ent.png" alt="">
        </div>
        <div class="value" id="total_enc">...</div>
    </div>
    <span>Encuestados</span>
</div>

<div class="block_term" data-tooltip="El estilo de aprendizaje más común entre los encuestados del curso.">
    <div class="flex">
        <div class="icon_term">
            <img src="../blocks/learning_style/dashboard/assets/grupo.png" alt="">
        </div>
        <div class="value" id="est_dom">...</div>
    </div>
    <span>Estilo dominante</span>
</div>

<div class="block_term" data-tooltip="El estilo de aprendizaje menos frecuente entre los encuestados del curso.">
    <div class="flex">
        <div class="icon_term">
            <img src="../blocks/learning_style/dashboard/assets/solo.png" alt="">
        </div>
        <div class="value" id="est_men_dom">...</div>
    </div>
    <span>Estilo menos dominante</span>
</div>

<div>
  <select id="chart-type-selector">
  <option value="radar">Perfil de estilos de aprendizaje</option>
    <option value="pie">Proporcion de estilos de aprendizaje</option>
    <option value="bar">Perfil cuantitativo de estilos de aprendizaje</option>
  </select>
</div>
<div class="c_graf">
    <canvas id="grafic" class="graf_term" style="width: 100%; height: auto;"></canvas>
</div>

<div class="c_graf" style="display: none">
    <canvas id="distr_pie" class="graf_term" style="width: 100%; height: auto;"></canvas>
</div>
<div class="c_graf" style="display: none">
    <canvas id="distr_bar" class="graf_term" height="300px"></canvas>
</div>
<div class="block_term">
    <div class="expandible">
        <div class="flex" id="expandir_actor">
            <div>
                Orden de dominancia
            </div>
            <button class="button_expandir">
                <img src="../blocks/learning_style/dashboard/assets/exp.png" alt="Expandir/Contraer" id="icon_exp">
            </button>
        </div>
        <div id="learning_style_exp" class="learning_style_exp_close">
            
        </div>
    </div>
</div>

<script src="../blocks/learning_style/dashboard/js/chart.js" ></script>
<script src="../blocks/learning_style/dashboard/js/main.js"></script>

