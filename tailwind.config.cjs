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
        'list-item': 'repeat(auto-fill, minmax(240px, 1fr))',
        'contents-item': 'repeat(auto-fill, minmax(200px, 1fr))',
      },
      colors: {
        'theme-bg1': 'rgb(var(--theme-primary-bg) / <alpha-value>)',
        'theme-fg1': 'hsl(var(--theme-primary-fg) / <alpha-value>)',
        'primary': 'rgb(var(--text-primary) / <alpha-value>)',
        'secondary': 'rgb(var(--text-secondary) / <alpha-value>)',
        'tertiary': 'rgb(var(--text-tertiary) / <alpha-value>)',
        'quaternary': 'rgb(var(--text-quaternary) / <alpha-value>)',
        'reader-paper': 'rgb(var(--reader-paper) / <alpha-value>)',
        'reader-bg': 'rgb(var(--reader-bg) / <alpha-value>)',
        
      }, // Extend Tailwind's default colors
      animation: {
        'fade-in-f': 'fade-in 0.4s',
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
            '--tw-translate-x': '-2rem',
            'opacity': 0,
          },
          '100%': {
            '--tw-translate-x': '0',
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
    },
  },
  plugins: [
    require('@tailwindcss/line-clamp'),
    require('@tailwindcss/typography'),
    plugin(function({ addVariant }) {
      addVariant('opened', '&.opened');
      addVariant('not-opened', '&:not(.opened)');
      addVariant('selected', '&.selected');
    }),
  ],
  safelist: [
    'dark',
  ]
};
