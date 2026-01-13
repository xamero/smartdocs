import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg width="40" height="42" viewBox="0 0 40 42" xmlns="http://www.w3.org/2000/svg">
           
            <rect width="40" height="42" fill="black" />

            <path
                d="M8 10H22L26 14V32H8V10Z"
                fill="none"
                stroke="white"
                strokeWidth="1.5"
                stroke-linejoin="round"
            />

          
            <line x1="11" y1="18" x2="21" y2="18" stroke="white" strokeWidth="1.5" />
            <line x1="11" y1="22" x2="21" y2="22" stroke="white" strokeWidth="1.5" />
            <line x1="11" y1="26" x2="21" y2="26" stroke="white" strokeWidth="1.5" />

          
            <path
                d="M27 7
       c-2 0-3.5 1.5-3.5 3.2
       c-1.4.4-2.2 1.6-2.2 2.8
       c0 2 1.6 3.2 3.6 3.2
       h5
       c2 0 3.6-1.2 3.6-3.2
       c0-1.4-1-2.6-2.4-3
       c-.4-1.6-1.8-3-3.9-3Z"
                fill="none"
                stroke="white"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />

            <line x1="27" y1="7" x2="27" y2="16" stroke="white" strokeWidth="1.5" />
        </svg>


    );
}
