import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    return {
        plugins: [vue()],
        build: {
            rollupOptions: {
              input: {
                'courseware-flow': 'src/courseware-flow.js',
              },
                output: {
                    entryFileNames: `[name].js`,
                    assetFileNames: (assetInfo) => {
                        if (assetInfo.name == 'style.css') return 'courseware-flow.css';
                        return assetInfo.name;
                    },
                },
            },
        },
        define: { 'process.env.NODE_ENV': `"${mode}"` },
    };
});

