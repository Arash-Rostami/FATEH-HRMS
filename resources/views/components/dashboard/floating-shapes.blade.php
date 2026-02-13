<div
    class="absolute inset-0 -z-10 overflow-hidden"
    x-show="open"
    x-data="floatingShapes()"
>
    <template x-for="shape in shapes" :key="`${shape.type}-${shape.color}-${shape.position.top}-${shape.position.left}`">
        <div
            data-shape
            class="absolute will-change-transform"
            style="transform: translateZ(0);"
            :style="getShapeStyle(shape)"
        ></div>
    </template>
</div>
<?php
