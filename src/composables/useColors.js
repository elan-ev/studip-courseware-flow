import { computed } from 'vue';

export function useColors() {
    const unitColors = computed(() => [
        { name: 'black', hex: '#000000' },
        { name: 'white', hex: '#ffffff' },
        { name: 'studip-blue', hex: '#28497c' },
        { name: 'studip-lightblue', hex: '#e7ebf1' },
        { name: 'studip-red', hex: '#d60000' },
        { name: 'studip-green', hex: '#008512' },
        { name: 'studip-yellow', hex: '#ffbd33' },
        { name: 'studip-gray', hex: '#636a71' },
        { name: 'charcoal', hex: '#3c454e' },
        { name: 'royal-purple', hex: '#8656a2' },
        { name: 'iguana-green', hex: '#66b570' },
        { name: 'queen-blue', hex: '#536d96' },
        { name: 'verdigris', hex: '#41afaa' },
        { name: 'mulberry', hex: '#bf5796' },
        { name: 'pumpkin', hex: '#f26e00' },
        { name: 'sunglow', hex: '#ffca5c' },
        { name: 'apple-green', hex: '#8bbd40' },
    ]);

    const getHexByColorName = (colorname) => {
        const color = unitColors.value.find(c => c.name === colorname);
        return color ? color.hex : null;
    };

    return { unitColors, getHexByColorName };
}
