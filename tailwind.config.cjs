// https://tailwindcss.com/docs/configuration
const plugin = require('tailwindcss/plugin');

module.exports = {
  content: ['./index.php', './app/**/*.php', './resources/**/*.{php,vue,js}'],
  theme: {
    extend: {
      backdropContrast: {
        25: '.25',
        90: '.9',
        80: '.8',
      },
      gridTemplateColumns: {
        'list-item': 'repeat(auto-fill, minmax(300px, 1fr))',
        'contents-item': 'repeat(auto-fill, minmax(200px, 1fr))',
      },
      colors: {
        'theme-bg1': 'rgb(var(--theme-primary-bg) / <alpha-value>)',
        'theme-fg1': 'hsl(var(--theme-primary-fg) / <alpha-value>)',
        'primary': 'rgb(var(--text-primary) / <alpha-value>)',
        'secondary': 'rgb(var(--text-secondary) / <alpha-value>)',
        'tertiary': 'rgb(var(--text-tertiary) / <alpha-value>)',
        'quaternary': 'rgb(var(--text-quaternary) / <alpha-value>)',
        'quinary': 'rgb(var(--text-quinary) / <alpha-value>)',
        'primary-bg': 'rgb(var(--bg-primary) / <alpha-value>)',
        'secondary-bg': 'rgb(var(--bg-secondary) / <alpha-value>)',
        'tertiary-bg': 'rgb(var(--bg-tertiary) / <alpha-value>)',
        'quaternary-bg': 'rgb(var(--bg-quaternary) / <alpha-value>)',
        'reader-paper': 'rgb(var(--reader-paper) / <alpha-value>)',
        'reader-bg': 'rgb(var(--reader-bg) / <alpha-value>)',

      }, // Extend Tailwind's default colors
      animation: {
        'fade-in-f': 'fade-in 0.4s',
        'fade-out-expand': 'fade-out-expand 2s infinite',
        'bounce-light': 'bounce-light 0.8s infinite',
        'elastic-f': 'elastic 0.4s',
        'spin-s': 'spin 10s linear infinite',
        'peek-in-l': 'peek-in-l 1s',
        'peek-in-t': 'peek-in-t 1s',
        'peek-in-t-f': 'peek-in-t 0.4s',
        'peek-in-b-f': 'peek-in-b 0.4s',
        'rotate': 'spin 1s',

      },
      keyframes: {
        'fade-in': {
          '0%': {
            'opacity': 0,
          },
          '100%': {
            'opacity': 1
          }
        },
        'fade-out-expand': {
          '0%': {
            'transform': 'scale(0)',
            'opacity': '1',
          },
          '100%': {
            'transform': 'scale(1)',
            'opacity': '0',
          }
        },
        'peek-in-l': {
          '0%': {
            'transform': 'translateX(-30px)',
            'opacity': 0,
          },
          '100%': {
            'transform': 'translateX(0)',
            'opacity': 1
          }
        },
        'peek-in-t': {
          '0%': {
            'transform': 'translateY(-2rem)',
            'opacity': 0,
          },
          '100%': {
            'transform': 'translateY(0)',
            'opacity': 1
          }
        },
        'peek-in-b': {
          '0%': {
            'transform': 'translateY(30px)',
            'opacity': 0,
          },
          '100%': {
            'transform': 'translateY(0)',
            'opacity': 1
          }
        },
        'bounce-light': {
          '0%, 100%': {
            'transform': 'translateY(-10%)',
            'animation-timing-function': 'cubic-bezier(0.8, 0, 1, 1)'
          },
          '50%': {
            'transform': 'translateY(0)',
            'animation-timing-function': 'cubic-bezier(0, 0, 0.2, 1)'
          }
        },
        'elastic': {
          '0%': {
            'transform': 'scale(0)',
          },

          '55%': {
            'transform': 'scale(1)',
          },

          '70%': {
            'transform': 'scale(.95)',
          },

          '100%': {
            'transform': 'scale(1)',
          }
        }
      },
      margin: {
        'xfit-md': 'calc(max(1rem,20vw - 6rem))',
        'xfit-lg': 'calc(max(1rem,25vw - 15rem))',
      },
      height: {
        '1/10': '10%',
      },
      // boxShadow: {
      //   'fill': '0 0 0 1px rgba(0, 0, 0, #000)'
      // }
    },
  },
  plugins: [
    require('@tailwindcss/line-clamp'),
    require('@tailwindcss/typography'),
    require('tailwind-scrollbar')({ nocompatible: true }),
    plugin(function ({ addVariant }) {
      addVariant('opened', '&.opened');
      addVariant('not-opened', '&:not(.opened)');
      addVariant('show', '&.show');
      addVariant('not-show', '&:not(.show)');
      addVariant('selected', '&.selected');
      addVariant('success', '&.style-success');
      addVariant('warning', '&.style-warning');
      addVariant('error', '&.style-error');
    }),
    plugin(function ({ addUtilities }) {
      addUtilities({
        '.gutter-auto': {
          'scrollbar-gutter': 'auto',
        },
        '.gutter-stable': {
          'scrollbar-gutter': 'stable',
        },
      })
    }),
  ],
  safelist: [
    'dark',
  ]
};
