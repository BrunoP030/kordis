import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import api from './api'

window.Pusher = Pusher

let instancia = null

// Reverb fala o protocolo do Pusher. A autorização do canal privado passa pelo
// axios (api.js), que já envia o cookie de sessão do Sanctum e o token XSRF.
export function obterEcho() {
  if (instancia) return instancia

  instancia = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => ({
      authorize: (socketId, callback) => {
        api
          .post('/broadcasting/auth', { socket_id: socketId, channel_name: channel.name })
          .then((resposta) => callback(null, resposta.data))
          .catch((erro) => callback(erro))
      },
    }),
  })

  return instancia
}
