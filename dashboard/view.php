<link rel="stylesheet" href="../blocks/learning_style/dashboard/css/style.css">
<div class="block_term">
    <div class="flex">
        <div class="icon_term">
            <img src="../blocks/learning_style/dashboard/assets/ent.png" alt="">
        </div>
        <div class="value" id="total_enc">...</div>
    </div>
    <span>
        Encuestados
    </span>
</div>
<div class="block_term">
    <div class="flex">
        <div class="icon_term">
            <img src="../blocks/learning_style/dashboard/assets/grupo.png" alt="">
        </div>
        <div class="value" id="est_dom">...</div>
    </div>
    <span>
        Estilo dominante
    </span>
</div>
<div class="block_term">
    <div class="flex">
        <div class="icon_term">
            <img src="../blocks/learning_style/dashboard/assets/solo.png" alt="">
        </div>
        <div class="value" id="est_men_dom">...</div>
    </div>
    <span>
        Estilo menos dominante
    </span>
</div>
<div class="c_graf">
    <canvas id="distr_pie" class="graf_term" style="width: 100%; height: auto;"></canvas>
</div>
<div class="c_graf">
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

