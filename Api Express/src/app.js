import express from "express"
import authRouter from "./router/AuthRouter.js"
import thirdPartiesRouter from "./router/ThirdpartiesRouter.js"
import productRouter from "./router/ProductRouter.js"
import orderRouter from "./router/OrderRouter.js"
import setupRouter from "./router/SetupRouter.js"
import 'dotenv/config'
import cors from "cors"
import proposalRouter from "./router/ProposalRouter.js"
import selloutRouter from "./router/SelloutRouter.js"
import discountRouter from "./router/DiscountRouter.js"
import shipmentRouter from "./router/ShipmentRouter.js"
import agendaRouter from "./router/AgendaRouter.js"
import userRouter from "./router/UserRouter.js"
import btocStatsRouter from "./router/BtocStatsRouter.js"

const app = express()

app.use(cors({
  origin: [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost:8083',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:8083'
  ],
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization', 'DOLAPIKEY']
}))
 

const PORT = process.env.PORT || 3000

app.use(express.json())

app.use("/api/auth",authRouter)
app.use("/api/proposals",proposalRouter)
app.use("/api/thirdparties",thirdPartiesRouter)
app.use("/api/products",productRouter)
app.use("/api/orders",orderRouter)
app.use("/api/setup",setupRouter)
app.use("/api/sellout", selloutRouter)
app.use("/api/discounts", discountRouter)
app.use("/api/shipments", shipmentRouter)
app.use("/api/agenda", agendaRouter)
app.use("/api/users", userRouter)
app.use("/api/btoc-stats", btocStatsRouter)

app.listen(PORT,()=>{
    console.log(`Router listen port : ${PORT}`)
})
