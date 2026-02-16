<div {{ $attributes->merge(['class' => 'flex justify-center items-center w-full h-full min-h-[200px]']) }}>
    <svg class="animate-spin h-12 w-12" viewBox="0 0 50 50">
        <circle
            class="path"
            cx="25"
            cy="25"
            r="20"
            fill="none"
            stroke-width="5"
        ></circle>
    </svg>

    <style>
        .path {
            stroke-linecap: round;
            animation: dash 1.5s ease-in-out infinite, colors 6s ease-in-out infinite;
        }

        @keyframes dash {
            0% {
                stroke-dasharray: 1, 150;
                stroke-dashoffset: 0;
            }
            50% {
                stroke-dasharray: 90, 150;
                stroke-dashoffset: -35;
            }
            100% {
                stroke-dasharray: 90, 150;
                stroke-dashoffset: -124;
            }
        }

        @keyframes colors {
            0% { stroke: #4285F4; }
            25% { stroke: #DB4437; }
            50% { stroke: #F4B400; }
            75% { stroke: #0F9D58; }
            100% { stroke: #4285F4; }
        }
    </style>
</div>
